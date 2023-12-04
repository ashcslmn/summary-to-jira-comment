<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extension\SummaryToJiraComment;

use MultiHttpClient;

class Hooks {

	/**
	 * Hook to handle saving of comments
	 * @param \MediaWiki\Revision\RenderedRevision $renderedRevision
	 * @param \MediaWiki\User\UserIdentity $user
	 * @param \CommentStoreComment $summary
	 * @param int $flags
	 * @param \Status $hookStatus
	 * @return bool
	 */
	public static function onMultiContentSave(
		\MediaWiki\Revision\RenderedRevision $renderedRevision,
		\MediaWiki\User\UserIdentity $user,
		\CommentStoreComment $summary,
		$flags,
		\Status $hookStatus
	) {
		$config = [
			\MediaWiki\MediaWikiServices::getInstance()->getMainConfig()->get( 'SummaryToJiraCommentInstance' ),
			\MediaWiki\MediaWikiServices::getInstance()->getMainConfig()->get( 'SummaryToJiraCommentToken' ),
			\MediaWiki\MediaWikiServices::getInstance()->getMainConfig()->get( 'SummaryToJiraCommentEmail' )
		];

		$issueKeys = self::getJiraIssueKeys( $summary->text );

		foreach ( $issueKeys as $issueKey ) {
			self::sendToJira( $config, $issueKey, $summary->text, $hookStatus );
		}

		return true;
	}

	/**
	 * strip out the issue keys from the summary
	 * @param string $summary
	 * @return array
	 */
	private static function getJiraIssueKeys( $summary ): array {
		$issueKeys = [];
		$issueKeyRegex = '/([A-Z]+-[0-9]+)/';
		$matches = [];
		preg_match_all( $issueKeyRegex, $summary, $matches );
		if ( isset( $matches[1] ) ) {
			$issueKeys = $matches[1];
		}
		return $issueKeys;
	}

	/**
	 * Send the comment to Jira using the Jira API
	 * @param array $config
	 * @param string $issueKey
	 * @param string $summary
	 * @param \Status $hookStatus
	 * @return bool
	 */
	private static function sendToJira( $config, $issueKey, $summary, $hookStatus ): bool {
		list( $instance, $token, $email ) = $config;
		$hash = base64_encode( $email . ':' . $token );

		$httpClient = new MultiHttpClient( [ 'maxRetries' => 3 ] );

		$response = $httpClient->run( [
			'headers' => [
				'Authorization' => 'Basic ' . $hash,
				'Content-Type' => 'application/json',
			],
			'url' => 'https://' . $instance . '/rest/api/2/issue/' . $issueKey . '/comment',
			'method' => 'POST',
			'body' => json_encode( [
				'body' => trim( str_replace( $issueKey, '', $summary ) )
			] )
		] );

		if ( $response['code'] !== 201 ) {
			$hookStatus->fatal( new \RawMessage( 'Failed to send comment to Jira' ) );
			return false;
		}

		return true;
	}
}
