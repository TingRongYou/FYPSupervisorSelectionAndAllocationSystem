import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.model.FailureHandling as FailureHandling

TestObject byXpath(String name, String xpath) {
	TestObject obj = new TestObject(name)
	obj.addProperty('xpath', ConditionType.EQUALS, xpath)
	return obj
}

String baseUrl = 'http://localhost/ssas'

String supervisorEmail = 'leezq1129@tarc.edu.my'
String supervisorPassword = 'leezq1129!'

String unmatchedKeyword = 'NO_MATCHING_REQUEST_999999'

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject pageTitle = byXpath('pageTitle', "//*[normalize-space()='Incoming Requests']")
TestObject searchInput = byXpath('searchInput', "//input[@name='search']")
TestObject statusSelect = byXpath('statusSelect', "//select[@name='status']")
TestObject applyFiltersButton = byXpath('applyFiltersButton', "//button[@type='submit' and normalize-space()='Apply Filters']")

TestObject noMatchingRequests = byXpath('noMatchingRequests', "//*[normalize-space()='No Matching Requests']")
TestObject noMatchingHint = byXpath('noMatchingHint', "//*[contains(normalize-space(),'Try changing the search, programme, or status filter')]")
TestObject viewProposalLink = byXpath('viewProposalLink', "//a[contains(@href,'supervisorRequestDecision.php') and normalize-space()='View Proposal']")

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, supervisorEmail)
WebUI.setText(passwordInput, supervisorPassword)
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

WebUI.navigateToUrl(baseUrl + '/client/supervisor/supervisorIncomingRequests.php')
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)

WebUI.setText(searchInput, unmatchedKeyword)
WebUI.selectOptionByLabel(statusSelect, 'All Statuses', false)
WebUI.click(applyFiltersButton)
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(noMatchingRequests, 10)
WebUI.verifyElementPresent(noMatchingHint, 10)
WebUI.verifyElementNotPresent(viewProposalLink, 3, FailureHandling.OPTIONAL)

WebUI.closeBrowser()