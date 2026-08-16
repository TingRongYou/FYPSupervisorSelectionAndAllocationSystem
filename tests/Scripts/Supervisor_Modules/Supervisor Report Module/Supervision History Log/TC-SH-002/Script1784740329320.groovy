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

// Use a future year so no matching supervision history records are returned.
String noRecordYear = '2099'

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject pageTitle = byXpath('pageTitle', "//*[normalize-space()='Supervision History']")
TestObject assignmentLog = byXpath('assignmentLog', "//*[normalize-space()='Assignment Log']")
TestObject emptyMessage = byXpath('emptyMessage', "//*[contains(@class,'empty-message')]")
TestObject firstHistoryRow = byXpath('firstHistoryRow', "//table[contains(@class,'report-table')]/tbody/tr[1]")

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, supervisorEmail)
WebUI.setText(passwordInput, supervisorPassword)
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

WebUI.navigateToUrl(baseUrl + '/client/supervisor/supervisorHistoryLog.php?year=' + noRecordYear + '&semester=1')
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)
WebUI.verifyElementPresent(assignmentLog, 10)
WebUI.verifyElementPresent(emptyMessage, 10)
WebUI.verifyElementNotPresent(firstHistoryRow, 3, FailureHandling.OPTIONAL)

WebUI.closeBrowser()