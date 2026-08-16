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

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject barcolaClassification = byXpath(
    'barcolaClassification',
    "//form[contains(@class,'data-row')][.//input[@name='supervisorID' and @value='5836']]//select[@name='employmentCategory']"
)

TestObject barcolaSaveButton = byXpath(
    'barcolaSaveButton',
    "//form[contains(@class,'data-row')][.//input[@name='supervisorID' and @value='5836']]//button[@type='submit']"
)

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')

WebUI.setText(emailInput, 'admin@tarc.edu.my')

// Use plain password first. Your encrypted password may not match.
WebUI.setText(passwordInput, 'admin7181!')

WebUI.click(loginButton)

// Go directly to Supervisor Management after login
WebUI.navigateToUrl(baseUrl + '/client/admin/supervisorsManagement.php')

WebUI.waitForPageLoad(10)
WebUI.verifyTextPresent('Supervisor Directory', false)
// Positive Test Case: valid classification update
WebUI.selectOptionByLabel(barcolaClassification, 'Full-Time Lecturer', false)

// Verify quota status updates immediately after dropdown change
WebUI.verifyTextPresent('0/30', false)
WebUI.verifyTextPresent('Available', false)

WebUI.click(barcolaSaveButton)

WebUI.waitForPageLoad(10)
WebUI.verifyTextPresent('Supervisor classification has been updated. The new base quota is now active.', false)
WebUI.verifyTextPresent('Full-Time Lecturer', false)
WebUI.verifyTextPresent('0/30', false)
WebUI.verifyTextPresent('Available', false)