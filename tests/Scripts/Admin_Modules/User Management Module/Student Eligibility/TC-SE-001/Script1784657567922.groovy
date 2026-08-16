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

// Change this if your Downloads file is actually CSV.
String csvPath = 'C:\\xampp\\htdocs\\ssas\\database\\sample_student_eligibility_import.csv'

// Login objects
TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

// Eligibility page objects
TestObject csvInput = byXpath('csvInput', "//input[@id='studentCSV' and @name='studentCSV']")
TestObject runBatchButton = byXpath('runBatchButton', "//button[normalize-space()='Run Eligibility Batch']")

TestObject eligibleStudentRow = byXpath(
    'eligibleStudentRow',
    "//article[contains(@class,'student-row')][.//*[contains(normalize-space(),'YONG CHONG XIN')] and .//span[contains(@class,'eligible') and normalize-space()='Eligible']]"
)

TestObject ineligibleStudentRow = byXpath(
    'ineligibleStudentRow',
    "//article[contains(@class,'student-row')][.//*[contains(normalize-space(),'TAN KAI CHUN')] and .//span[contains(@class,'ineligible') and normalize-space()='Ineligible']]"
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


// ======================================================
// TC-SE-001 Positive: Upload CSV then run eligibility batch
// ======================================================

WebUI.uploadFile(csvInput, csvPath)
WebUI.waitForPageLoad(10)

WebUI.verifyTextPresent('CSV imported successfully', false)

// After upload, button should become clickable
WebUI.verifyElementClickable(runBatchButton)

WebUI.click(runBatchButton)

// Confirm dialog appears from admin.js
WebUI.acceptAlert(FailureHandling.OPTIONAL)

WebUI.waitForPageLoad(10)

WebUI.verifyTextPresent('Batch Complete: Eligibility check completed', false)

// Verify positive student result
WebUI.verifyElementPresent(eligibleStudentRow, 10)
WebUI.verifyTextPresent('YONG CHONG XIN', false)
WebUI.verifyTextPresent('Eligible', false)

// Verify negative student result inside same batch result
WebUI.verifyElementPresent(ineligibleStudentRow, 10)
WebUI.verifyTextPresent('TAN KAI CHUN', false)
WebUI.verifyTextPresent('Ineligible', false)

WebUI.closeBrowser()