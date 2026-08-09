import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// Function 5.1 - TC002: Verify Structured Rating System (Negative - No Allocation)
// Decision Table: C1=F -> Expected = E2 (Abort form & show Access Error)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. VERIFICATION OBJECTS (Based on Claude Code DOM analysis)
TestObject emptyCardError = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//section[contains(@class, 'empty-card')]")
TestObject submitReviewBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' and text()='Submit Review']")

try {
    // Step 1: Navigate to login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter student credentials
    // NOTE: Use a student account that does NOT have an approved supervisor allocation!
    WebUI.setText(emailInput, 'rayden-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '50517101237')
    
    // Step 4: Click login
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate to Student Review page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentReview.php')
    WebUI.waitForPageLoad(15)
    
    // VERIFICATION: E2 - Check for the Access Error UI and ensure the form is aborted
    // 1. The "empty-card" warning should be visible
    WebUI.waitForElementVisible(emptyCardError, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyElementVisible(emptyCardError)
    
    // 2. The submit button (and thus the form) should NOT be present in the DOM
    WebUI.verifyElementNotPresent(submitReviewBtn, 3, FailureHandling.STOP_ON_FAILURE)

    println("F5.1-TC002 Passed: System successfully aborted form rendering and displayed the empty-card access error for an unallocated student.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F5.1-TC002 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}