import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// Function 5.1 - TC001: Verify Structured Rating System (Positive)
// Decision Table: C1=T, C2=T, C3=T, C4=T -> Expected = E1 (Commit review & show Success)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. REVIEW FORM OBJECTS
// We target the label rather than the radio input directly, as CSS star widgets often hide the actual radio button
TestObject starRating5Label = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//label[@for='rating5']")
TestObject feedbackTextarea = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//textarea[@id='textFeedback']")
TestObject submitReviewBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' and text()='Submit Review']")

// 3. SUCCESS VERIFICATION OBJECTS
TestObject completedBadge = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//div[@class='completed-review']//span[@class='badge' and text()='Completed']")

try {
    // Step 1: Navigate to login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter student credentials
    WebUI.setText(emailInput, 'marshell-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '51117103447')
    
    // Step 4: Click login
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate directly to the corrected Student Review page URL
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentReview.php')
    WebUI.waitForPageLoad(15)
    
    // Verify the form actually loaded (Validating preconditions C1 and C2 are True)
    WebUI.waitForElementVisible(submitReviewBtn, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 6: Select a mandatory numerical star rating (C3 = True)
    WebUI.click(starRating5Label)

    // Step 7: Enter text feedback <= 1000 characters (C4 = True)
    WebUI.setText(feedbackTextarea, 'Excellent guidance and support throughout the project. Highly recommended supervisor.')

    // Step 8: Submit the review
    WebUI.click(submitReviewBtn)
    
    WebUI.waitForPageLoad(15)

    // VERIFICATION: E1 - Verify redirection to "Completed" UI state
    // We check for the specific 'Completed' badge to ensure it didn't just crash or reload the empty form
    WebUI.waitForElementVisible(completedBadge, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyElementVisible(completedBadge)

    println("F5.1-TC001 Passed: Valid review was successfully committed to the database and the UI transitioned to the Completed state.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F5.1-TC001 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}