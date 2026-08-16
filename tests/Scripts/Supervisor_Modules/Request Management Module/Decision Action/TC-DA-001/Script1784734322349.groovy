import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.model.FailureHandling as FailureHandling
import com.kms.katalon.core.util.KeywordUtil

TestObject byXpath(String name, String xpath) {
	TestObject obj = new TestObject(name)
	obj.addProperty('xpath', ConditionType.EQUALS, xpath)
	return obj
}

String baseUrl = 'http://localhost/ssas'
String supervisorEmail = 'leezq1129@tarc.edu.my'
String supervisorPassword = 'leezq1129!'

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject incomingPageTitle = byXpath('incomingPageTitle', "//*[normalize-space()='Incoming Requests']")
TestObject firstViewProposal = byXpath('firstViewProposal', "(//a[contains(normalize-space(),'View Proposal')])[1]")

TestObject commentBox = byXpath('commentBox', "//textarea[@name='supervisorComment']")
TestObject rejectButton = byXpath('rejectButton', "//button[@type='submit' and @name='decisionStatus' and @value='Rejected']")
TestObject decisionRecorded = byXpath('decisionRecorded', "//*[normalize-space()='Decision Recorded']")

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, supervisorEmail)
WebUI.setText(passwordInput, supervisorPassword)
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

WebUI.navigateToUrl(baseUrl + '/client/supervisor/supervisorIncomingRequests.php?status=Pending')
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(incomingPageTitle, 10)

if (!WebUI.verifyElementPresent(firstViewProposal, 5, FailureHandling.OPTIONAL)) {
	KeywordUtil.markFailed('Pre-condition failed: no pending request is available for this supervisor.')
}

String proposalHref = WebUI.getAttribute(firstViewProposal, 'href')
WebUI.navigateToUrl(proposalHref)
WebUI.waitForPageLoad(10)

if (!WebUI.verifyElementPresent(commentBox, 10, FailureHandling.OPTIONAL)) {
	KeywordUtil.markFailed('Decision form is not available. The selected request may no longer be Pending.')
}

WebUI.scrollToElement(commentBox, 5)
WebUI.setText(commentBox, 'The proposal is not suitable for my current supervision area. Please revise the topic and resubmit.')

WebUI.scrollToElement(rejectButton, 5)
WebUI.click(rejectButton)
WebUI.waitForPageLoad(10)

WebUI.verifyTextPresent("Student's application for supervision has been rejected successfully.", false)
WebUI.verifyElementPresent(decisionRecorded, 10)
WebUI.verifyTextPresent('rejected', false)

WebUI.closeBrowser()