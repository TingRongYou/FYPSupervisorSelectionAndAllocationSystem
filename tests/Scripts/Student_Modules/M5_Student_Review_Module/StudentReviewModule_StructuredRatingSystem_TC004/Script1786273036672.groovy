import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// Function 5.1 - TC004: Verify Structured Rating System (Negative - Missing Star Rating)
// Decision Table: C1=T, C2=T, C3=F, C4=T -> Expected = E3 (Intercept & show JS Alert)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. REVIEW FORM OBJECTS
TestObject feedbackTextarea = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//textarea[@id='textFeedback']")
TestObject submitReviewBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' and text()='Submit Review']")

try {
    // Step 1: Navigate to login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter provided credentials
    WebUI.setText(emailInput, 'rayden-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '50517101237')
    
    // Step 4: Click login
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate to Student Review page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentReview.php')
    WebUI.waitForPageLoad(15)
    
    WebUI.waitForElementVisible(submitReviewBtn, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 6: Explicitly leave star rating unselected (C3 = False)

    // Step 7: Enter valid text feedback (C4 = True)
    WebUI.setText(feedbackTextarea, 'Helpful feedback provided during consultations.')

    // Step 8: Click "Submit Review"
    WebUI.click(submitReviewBtn)
    
    // VERIFICATION: E3 - Assert that the JavaScript alert appears with the exact warning message
    boolean isAlertPresent = WebUI.waitForAlert(5, FailureHandling.OPTIONAL)
    
    if (isAlertPresent) {
        String alertMsg = WebUI.getAlertText()
        println("SUCCESS: System intercepted missing star rating via alert. Alert text: " + alertMsg)
        
        // Assert the exact alert message text captured from the application
        assert alertMsg.contains("Submission failed. You must select a star rating (1-5)") : "Alert message text did not match expected validation error."
        
        WebUI.acceptAlert()
        println("F5.1-TC004 Passed: Expected JavaScript validation error alert was displayed successfully for missing star rating.")
    } else {
        throw new Exception("F5.1-TC004 Failed: Expected JavaScript alert was not displayed when submitting without a star rating.")
    }

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F5.1-TC004 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}