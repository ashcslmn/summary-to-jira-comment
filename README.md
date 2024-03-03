# Send Page Summary to Jira Comment

This extension sends the page summary to a Jira comment.

## Set up 

Prerequisite:
- MediaWiki running locally 
- Jira Account

1. Get the Jira API Access Token https://support.atlassian.com/atlassian-account/docs/manage-api-tokens-for-your-atlassian-account/

2. Create a project and issues.  

3. Install the extension
   
	```bash
	cd extensions
	git clone https://github.com/ashcslmn/summary-to-jira-comment.git SummaryToJiraComment
	```

4. Add the following to your LocalSettings.php

	```php 
	wfLoadExtension('SummaryToJiraComment');
	$wgSummaryToJiraCommentInstance = '<your instance>'; // e.g. <your instance>.atlassian.net
	$wgSummaryToJiraCommentToken = '<your token>';
	$wgSummaryToJiraCommentEmail = '<your email>';
	```
	
## Setting up a dedicated Jira user for bot 

from wikiteq, https://wikiteq.atlassian.net/browse/WIK-1385?focusedCommentId=59113

## Demo

https://www.loom.com/share/0b5675bbc56c49a8be819d45546d3eff

## NOTE:
This is only an poc and not production ready.

References:

https://phabricator.wikimedia.org/r/project/mediawiki/extensions/BoilerPlate/
