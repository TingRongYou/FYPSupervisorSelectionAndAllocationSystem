import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType

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

TestObject pageTitle = byXpath('pageTitle', "//*[normalize-space()='Incoming Requests']")
TestObject statusSelect = byXpath('statusSelect', "//select[@name='status']")
TestObject applyFiltersButton = byXpath('applyFiltersButton', "//button[@type='submit' and normalize-space()='Apply Filters']")

TestObject studentNameHeader = byXpath('studentNameHeader', "//th[normalize-space()='Student Name']")
TestObject studentIdHeader = byXpath('studentIdHeader', "//th[normalize-space()='Student ID']")
TestObject programmeHeader = byXpath('programmeHeader', "//th[normalize-space()='Programme']")
TestObject statusHeader = byXpath('statusHeader', "//th[normalize-space()='Status']")
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

// Use All Statuses to make the positive test less dependent on current pending/accepted status
WebUI.selectOptionByLabel(statusSelect, 'All Statuses', false)
WebUI.click(applyFiltersButton)
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)
WebUI.verifyElementPresent(studentNameHeader, 10)
WebUI.verifyElementPresent(studentIdHeader, 10)
WebUI.verifyElementPresent(programmeHeader, 10)
WebUI.verifyElementPresent(statusHeader, 10)
WebUI.verifyElementPresent(viewProposalLink, 10)

WebUI.closeBrowser()