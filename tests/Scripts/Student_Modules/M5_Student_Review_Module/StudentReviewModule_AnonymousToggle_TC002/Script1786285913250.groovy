import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// Function 5.2 - TC002: Verify Anonymous Toggle (Positive - Anonymous Disabled)
// Decision Table: C1=F -> Expected = E2 (Render True Name on UI & bind ID in backend)
// ==============================================================================

// 1. SHARED AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")
TestObject accountMenuContainer = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//div[contains(@class,'account-menu')]")
TestObject logoutLink = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//a[contains(@class,'logout-link')]")

// 2. STUDENT REVIEW FORM OBJECTS
TestObject starRating5Label = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//label[@for='rating5']")
TestObject feedbackTextarea = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//textarea[@id='textFeedback']")
TestObject submitReviewBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' and text()='Submit Review']")

// 3. SUPERVISOR REVIEW LISTING OBJECTS
TestObject reviewAuthorText = new TestObject().addProperty("xpath", ConditionType.EQUALS, "(//p[@class='review-author'])[1]")

try {
    // ==========================================
    // PART A: STUDENT SUBMISSION (Anonymous = False)
    // ==========================================
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.setText(emailInput, 'sherry-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '50517444447')
    WebUI.click(loginBtn)
    
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentReview.php')
    WebUI.waitForElementVisible(submitReviewBtn, 10, FailureHandling.STOP_ON_FAILURE)

    WebUI.click(starRating5Label)
    WebUI.setText(feedbackTextarea, 'Excellent supervision.')
    
    // Explicitly do NOT check the anonymous toggle (C1 = False)
    WebUI.click(submitReviewBtn)
    WebUI.waitForPageLoad(15)

    WebUI.mouseOver(accountMenuContainer)
    WebUI.waitForElementVisible(logoutLink, 5)
    WebUI.click(logoutLink)

    // ==========================================
    // PART B: SUPERVISOR VERIFICATION
    // ==========================================
    WebUI.setText(emailInput, 'leezq1129@tarc.edu.my')
    WebUI.setText(passwordInput, 'LeeZQ7181@')
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    WebUI.navigateToUrl('http://localhost/ssas/client/supervisor/supervisorStudentReviews.php')
    WebUI.waitForElementVisible(reviewAuthorText, 10, FailureHandling.STOP_ON_FAILURE)

    // VERIFICATION: Verify True Name is rendered (e.g., "Rayden" or full name in DB)
    String renderedAuthor = WebUI.getText(reviewAuthorText)
    println("Author rendered as: " + renderedAuthor)
    
    // Assert that it is NOT anonymous
    assert !renderedAuthor.contains("Anonymous Student") : "Review was expected to show true name, but showed Anonymous Student."

    println("F5.2-TC002 Passed: Toggle left deactivated (C1=F), rendering true student identity on supervisor view.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F5.2-TC002 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}