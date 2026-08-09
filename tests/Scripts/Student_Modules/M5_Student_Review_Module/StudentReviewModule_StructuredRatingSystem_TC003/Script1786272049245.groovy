import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// Function 5.1 - TC003: Verify Structured Rating System (Negative - Pre-existing Review)
// Decision Table: C1=T, C2=F -> Expected = E2 (Abort form & show "Already Reviewed" prompt)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. VERIFICATION OBJECTS (Based on studentReview.php structure)
TestObject completedBadge = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//div[@class='completed-review']//span[@class='badge' and text()='Completed']")
TestObject submitReviewBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' and text()='Submit Review']")

try {
    // Step 1: Navigate to login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter student credentials
    // NOTE: Use a student account that has ALREADY submitted a review for this allocation (C2 = False)
    WebUI.setText(emailInput, 'marshell-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '51117103447')
    
    // Step 4: Click login
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate to Student Review page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentReview.php')
    WebUI.waitForPageLoad(15)
    
    // VERIFICATION: E2 - Check that the 'Completed' review prompt is shown and the form is aborted
    // 1. The completed badge and message container must be visible
    WebUI.waitForElementVisible(completedBadge, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyElementVisible(completedBadge)
    
    // 2. The submit button must NOT be present in the DOM (form form rendering aborted)
    WebUI.verifyElementNotPresent(submitReviewBtn, 3, FailureHandling.STOP_ON_FAILURE)

    println("F5.1-TC003 Passed: System successfully aborted form rendering and displayed the 'Already Reviewed' prompt for a pre-existing review.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F5.1-TC003 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}