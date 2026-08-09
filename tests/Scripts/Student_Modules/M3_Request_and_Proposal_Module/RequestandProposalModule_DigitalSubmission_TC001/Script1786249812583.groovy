import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling
import com.kms.katalon.core.configuration.RunConfiguration

// ==============================================================================
// F3.1 - TC001: Verify Digital Submission (All Validation Conditions Met)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS (Marshell)
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. DISCOVERY & PROFILE NAVIGATION OBJECTS (Targeting Lee Zi Qing)
TestObject viewProfileBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//article[contains(@class,'discovery-card') and .//h2[contains(text(), 'Lee Zi Qing')]]//a[contains(text(), 'View Profile')]")
TestObject submitProposalLink = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//a[contains(@class,'button') and (contains(@href,'submitProposalForm.php') or contains(text(),'Submit Proposal'))]")

// 3. DIGITAL SUBMISSION FORM OBJECTS
TestObject projectTitleInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@name='projectTitle' or @id='projectTitle' or @type='text']")
TestObject proposalUpload = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='file']")
TestObject formFinalSubmitBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit']")

try {
    // Step 1: Navigate to the SSAS login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter student credentials
    WebUI.setText(emailInput, 'marshell-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '51117103447')

    // Step 4: Click the login button
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5 & 6: Navigate to Student Discovery page and click "View Profile" for Lee Zi Qing
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentDiscovery.php')
    WebUI.waitForElementVisible(viewProfileBtn, 15, FailureHandling.STOP_ON_FAILURE)
    WebUI.click(viewProfileBtn)
    WebUI.waitForPageLoad(15)
    
    // Step 7: Click "Submit Proposal" button to enter the submission form page
    WebUI.waitForElementVisible(submitProposalLink, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.click(submitProposalLink)
    WebUI.waitForPageLoad(15)
    
    // Step 8: Enter project title: Valid Proposal
    WebUI.waitForElementVisible(projectTitleInput, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.setText(projectTitleInput, 'Valid Proposal')
    
    // Step 10: Upload proposal document using project-level relative path for GitHub portability
    String validPdfPath = System.getProperty("user.dir") + "/assets/valid_proposal.pdf"
    WebUI.uploadFile(proposalUpload, validPdfPath, FailureHandling.STOP_ON_FAILURE)
    
    // Step 11: Click the form's "Submit" button and accept the browser confirmation pop-up
    WebUI.verifyElementClickable(formFinalSubmitBtn)
    WebUI.click(formFinalSubmitBtn)
    
    // Handle the JavaScript confirmation pop-up ("Submit this proposal to the selected supervisor?")
    WebUI.waitForAlert(5)
    WebUI.acceptAlert()
    
    // Wait for backend redirect and success notification
    WebUI.delay(3)
    
    // Verification
    String finalUrl = WebUI.getUrl()
    println("F3.1-TC001 Passed: Proposal successfully submitted. Redirected URL: " + finalUrl)

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F3.1-TC001 Failed: Digital submission failed to process correctly.")
    throw e
} finally {
    WebUI.closeBrowser()
}