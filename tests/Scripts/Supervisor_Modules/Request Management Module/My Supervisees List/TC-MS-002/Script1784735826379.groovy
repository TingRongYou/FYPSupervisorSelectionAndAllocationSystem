import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType

TestObject byXpath(String name, String xpath) {
	TestObject obj = new TestObject(name)
	obj.addProperty('xpath', ConditionType.EQUALS, xpath)
	return obj
}

String baseUrl = 'http://localhost/ssas'

// Use a supervisor account with no assigned supervisees for this negative test.
String supervisorEmail = 'barcola@tarc.edu.my'
String supervisorPassword = 'barcola7777'

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject pageTitle = byXpath('pageTitle', "//*[normalize-space()='My Supervisees']")
TestObject subtitle = byXpath('subtitle', "//*[contains(normalize-space(),'Students under your supervision')]")
TestObject emptyState = byXpath('emptyState', "//*[contains(normalize-space(),'No supervisees') and contains(normalize-space(),'Your supervisees list is currently empty')]")
TestObject tableRow = byXpath('tableRow', "//table/tbody/tr[1]")

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, supervisorEmail)
WebUI.setText(passwordInput, supervisorPassword)
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

WebUI.navigateToUrl(baseUrl + '/client/supervisor/supervisorMySupervisees.php')
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)
WebUI.verifyElementPresent(subtitle, 10)

WebUI.verifyElementPresent(emptyState, 10)
WebUI.verifyElementNotPresent(tableRow, 3)

WebUI.closeBrowser()