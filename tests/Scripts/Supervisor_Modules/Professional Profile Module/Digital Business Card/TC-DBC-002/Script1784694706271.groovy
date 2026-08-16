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

String bioValue = 'This biography remains valid while the active time is intentionally left blank for negative testing.'

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject pageTitle = byXpath('pageTitle', "//*[normalize-space()='Digital Business Card']")
TestObject activeTimeInput = byXpath('activeTimeInput', "//input[@name='activeTime']")
TestObject bioTextarea = byXpath('bioTextarea', "//textarea[@name='supervisorBio']")
TestObject saveButton = byXpath('saveButton', "//button[@type='submit' and normalize-space()='Save Card Changes']")

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, supervisorEmail)
WebUI.setText(passwordInput, supervisorPassword)
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

WebUI.navigateToUrl(baseUrl + '/client/supervisor/manageDigitalBusinessCard.php')
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)

WebUI.clearText(activeTimeInput)

WebUI.clearText(bioTextarea)
WebUI.setText(bioTextarea, bioValue)

WebUI.click(saveButton)

// Because activeTime is required, the browser should block submission.
// Therefore the page should still be the Digital Business Card page.
WebUI.delay(1)
WebUI.verifyElementPresent(pageTitle, 10)
WebUI.verifyElementPresent(activeTimeInput, 10)

// Confirm the success message is not shown.
TestObject successMessage = byXpath('successMessage', "//*[contains(normalize-space(),'Update Successful')]")
WebUI.verifyElementNotPresent(successMessage, 3, FailureHandling.OPTIONAL)

WebUI.closeBrowser()