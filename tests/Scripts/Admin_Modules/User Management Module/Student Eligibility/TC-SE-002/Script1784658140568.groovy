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

// Login objects
TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

// Eligibility page objects
TestObject runBatchButton = byXpath(
    'runBatchButton',
    "//button[normalize-space()='Run Eligibility Batch']"
)

// Login
WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')

WebUI.setText(emailInput, 'admin@tarc.edu.my')
WebUI.setText(passwordInput, 'admin7181!')

WebUI.click(loginButton)

WebUI.waitForPageLoad(10)

// Go to Student Eligibility page
WebUI.navigateToUrl(baseUrl + '/client/admin/studentEligibility.php')

WebUI.waitForPageLoad(10)

WebUI.verifyTextPresent('Student Eligibility Management', false)

// TC-SE-002 Negative:
// Without uploading CSV, Run Eligibility Batch should not be clickable
WebUI.verifyElementNotClickable(runBatchButton)

WebUI.closeBrowser()