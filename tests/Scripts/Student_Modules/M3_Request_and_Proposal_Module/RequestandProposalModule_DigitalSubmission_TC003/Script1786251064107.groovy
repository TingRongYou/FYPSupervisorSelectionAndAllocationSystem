import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F3.1 - TC003: Verify Digital Submission (File Size Exceeded Mismatch)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS (Marshell)
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. NAVIGATION OBJECTS
TestObject viewProfileBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//article[contains(@class,'discovery-card') and .//h2[contains(text(), 'Lee Zi Qing')]]//a[contains(text(), 'View Profile')]")
TestObject submitProposalLink = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//a[contains(@class,'button') and (contains(@href,'submitProposalForm.php') or contains(text(),'Submit Proposal'))]")

// 3. FORM OBJECTS
TestObject projectTitleInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@name='projectTitle' or @id='projectTitle' or @type='text']")
TestObject proposalUpload = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='file']")
TestObject formFinalSubmitBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit']")

// 4. VALIDATION ERROR MESSAGE OBJECT
TestObject errorMessage = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//*[contains(@class,'error') or contains(text(),'size') or contains(text(),'MB') or contains(@class,'alert')]")

try {
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    WebUI.setText(emailInput, 'marshell-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '51117103447')
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentDiscovery.php')
    WebUI.waitForElementVisible(viewProfileBtn, 15, FailureHandling.STOP_ON_FAILURE)
    WebUI.click(viewProfileBtn)
    WebUI.waitForPageLoad(15)
    
    WebUI.waitForElementVisible(submitProposalLink, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.click(submitProposalLink)
    WebUI.waitForPageLoad(15)
    
    // Step 8: Enter valid project title
    WebUI.waitForElementVisible(projectTitleInput, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.setText(projectTitleInput, 'Oversized Proposal Test')
    
    // Step 9: Upload an oversized PDF (> 5.0 MB) from your project assets folder
    String oversizedPdfPath = System.getProperty("user.dir") + "/assets/invalid_oversized_proposal.pdf"
    WebUI.uploadFile(proposalUpload, oversizedPdfPath, FailureHandling.STOP_ON_FAILURE)
    
    // Step 10: Click submit
    WebUI.click(formFinalSubmitBtn)
    WebUI.delay(2)
    
    // VERIFICATION: Verify that submission was intercepted and an error message is visible
    WebUI.waitForElementVisible(errorMessage, 5, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyElementVisible(errorMessage)
    
    println("F3.1-TC003 Passed: Submission successfully intercepted due to file size exceeding 5.0 MB.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F3.1-TC003 Failed: System incorrectly allowed submission with an oversized file.")
    throw e
} finally {
    WebUI.closeBrowser()
}