import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling
import com.kms.katalon.core.webui.common.WebUiCommonHelper
import org.openqa.selenium.WebElement

// ==============================================================================
// Function 5.1 - TC005: Verify Structured Rating System (Negative - Feedback > 1000 Chars)
// Decision Table: C1=T, C2=T, C3=T, C4=F -> Expected = E3 (Intercept / Restrict Input)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. REVIEW FORM OBJECTS
TestObject starRating3Label = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//label[@for='rating3']")
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

    // Step 6: Select a valid star rating (C3 = True)
    WebUI.click(starRating3Label)

    // Step 7: Attempt to input 1001 characters into the feedback textarea (C4 = False)
    String overLimitFeedback = "A" * 1001
    WebUI.setText(feedbackTextarea, overLimitFeedback)

    // VERIFICATION: Check that the HTML5 maxlength attribute truncated the input to exactly 1000 characters
    String currentFeedbackValue = WebUI.getAttribute(feedbackTextarea, 'value')
    
    if (currentFeedbackValue.length() == 1000) {
        println("SUCCESS: Browser natively restricted feedback input to 1000 characters.")
        println("F5.1-TC005 Passed: Text feedback exceeding the limit was successfully intercepted and truncated for C4=False.")
    } else {
        throw new Exception("F5.1-TC005 Failed: Feedback textarea accepted " + currentFeedbackValue.length() + " characters instead of restricting to 1000.")
    }

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F5.1-TC005 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}