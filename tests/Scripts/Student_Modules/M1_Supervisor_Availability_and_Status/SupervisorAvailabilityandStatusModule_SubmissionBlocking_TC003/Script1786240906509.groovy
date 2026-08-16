import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F1.3 - TC003: Block Application Submission (Max Pending Requests Reached)
// ==============================================================================

// 1. SHARED AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. DISCOVERY & PROFILE OBJECTS
TestObject viewProfileBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//article[contains(@class,'discovery-card') and .//h2[contains(text(), 'Lee Zi Qing')]]//a[contains(text(), 'View Profile')]")
TestObject submitProposalLink = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//a[contains(@class,'button') and contains(@href,'submitProposalForm.php')]")

// 3. APPLICATION FORM OBJECTS (New objects for Steps 8-10)
TestObject projectTitleInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@name='projectTitle' or @id='projectTitle' or @type='text']")
TestObject proposalUpload = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='file']")
TestObject formFinalSubmitBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' and contains(translate(text(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'submit')]")

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

    // Step 5: Navigate explicitly to the Student Discovery page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentDiscovery.php')
    WebUI.waitForElementVisible(viewProfileBtn, 15, FailureHandling.STOP_ON_FAILURE)

    // Step 6: Click "View Profile" for Lee Zi Qing
    WebUI.click(viewProfileBtn)
    WebUI.waitForPageLoad(15)
    
    // Step 7: Click the active "Submit Proposal" link to enter the form
    WebUI.waitForElementVisible(submitProposalLink, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.click(submitProposalLink)
    WebUI.waitForPageLoad(15)
    
    // Step 8: Enter the project title
    WebUI.waitForElementVisible(projectTitleInput, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.setText(projectTitleInput, 'Test title')
    
    // Step 9: Upload a project proposal document
    // IMPORTANT: Update this string to a valid PDF path on your computer!
    String dummyFilePath = "C:\\xampp\\htdocs\\ssas\\tests\\assets\\valid_proposal.pdf" 
    WebUI.uploadFile(proposalUpload, dummyFilePath, FailureHandling.STOP_ON_FAILURE)
    
    // Step 10 (Labeled 8 in template): Click the "Submit Proposal" button
    WebUI.verifyElementClickable(formFinalSubmitBtn)
    WebUI.click(formFinalSubmitBtn)
    
    // Handle the JavaScript confirmation pop-up!
    WebUI.waitForAlert(5)
    WebUI.acceptAlert()
    
    // Wait for the backend POST request to process and redirect
    WebUI.delay(3)
    
    // VERIFICATION: Ensure the backend blocked the request and appended the error URL parameters
    String finalUrl = WebUI.getUrl()
    
    // Verify the URL contains the exact 3-request limit error message from the backend
    WebUI.verifyMatch(finalUrl, '.*You\\+may\\+only\\+have\\+up\\+to\\+3.*', true, FailureHandling.STOP_ON_FAILURE)
    
    println("F1.3-TC003 Passed: The backend successfully blocked the submission and returned the limit error!")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F1.3-TC003 Failed: The system did not block the request or return the expected URL error parameters.")
    throw e
} finally {
    WebUI.closeBrowser()
}