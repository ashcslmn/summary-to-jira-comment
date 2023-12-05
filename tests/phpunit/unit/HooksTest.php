<?php

namespace MediaWiki\Extension\SummaryToJiraComment\Tests;

/**
 * @coversDefaultClass \MediaWiki\Extension\SummaryToJiraComment\Hooks
 */
class HooksTest extends \MediaWikiUnitTestCase {

	/**
	 * @covers ::onPageSaveComplete
	 */
	public function testOnPageSaveComplete() {
		$wikiPage = $this->createMock( WikiPage::class );
		$user = $this->createMock( UserIdentity::class );
		$summary = 'Test summary';
		$flags = 0;

		$revisionRecord = $this->createMock( RevisionRecord::class );
		$editResult = $this->createMock( EditResult::class );
		$result = Hooks::onPageSaveComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult );

		$this->assertTrue( $result );
	}

	/**
	 * @covers ::sendToJira
	 */
	public function testSendToJira() {
		$config = [
		   'instance' => 'https://jira.example.com',
		   'token' => 'token',
		   'email' => 'test@example.test'
		];
		$issueKey = 'TEST-1';
		$summary = 'Test summary';

		$httpClient = $this->createMock( MultiHttpClient::class );
		// body should be any non-empty array
		$response = [ 'code' => 201, 'body' => [ 'foo' => 'bar' ] ];
		$httpClient->method( 'run' )->willReturn( $response );

		Hooks::$httpClient = $httpClient;
		$result = Hooks::sendToJira( $config, $issueKey, $summary );

		$this->assertTrue( $result );
	}
}
