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

String activeTimeValue = 'Monday 2:00 PM - 4:00 PM'
String bioValue = 'I supervise final year projects related to web application development, database design, and applied software engineering.'

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject pageTitle = byXpath('pageTitle', "//*[normalize-space()='Digital Business Card']")
TestObject activeTimeInput = byXpath('activeTimeInput', "//input[@name='activeTime']")
TestObject bioTextarea = byXpath('bioTextarea', "//textarea[@name='supervisorBio']")
TestObject saveButton = byXpath('saveButton', "//button[@type='submit' and normalize-space()='Save Card Changes']")
TestObject successMessage = byXpath('successMessage', "//*[contains(normalize-space(),'Update Successful')]")

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
WebUI.setText(activeTimeInput, activeTimeValue)

WebUI.clearText(bioTextarea)
WebUI.setText(bioTextarea, bioValue)

WebUI.click(saveButton)

// Accept confirmation popup
WebUI.acceptAlert(FailureHandling.OPTIONAL)

WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(successMessage, 10)
WebUI.verifyTextPresent(activeTimeValue, false)
WebUI.verifyTextPresent(bioValue, false)

WebUI.closeBrowser()