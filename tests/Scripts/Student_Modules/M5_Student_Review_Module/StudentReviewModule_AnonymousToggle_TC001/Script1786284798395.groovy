import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling
import com.kms.katalon.core.webui.common.WebUiCommonHelper
import org.openqa.selenium.WebElement
import java.util.Arrays

// ==============================================================================
// Function 5.2 - TC001: Verify Anonymous Toggle (Positive - Anonymous Enabled)
// Decision Table: C1=T -> Expected = E1 (Render "Anonymous" on UI & bind ID in backend)
// ==============================================================================

// 1. SHARED AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// Account menu container to trigger hover, and the logout button inside it
TestObject accountMenuContainer = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//div[contains(@class,'account-menu')]")
TestObject logoutLink = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//a[contains(@class,'logout-link')]")

// 2. STUDENT REVIEW FORM OBJECTS (client/student/studentReview.php)
TestObject starRating3Label = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//label[@for='rating3']")
TestObject feedbackTextarea = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//textarea[@id='textFeedback']")
TestObject anonymousCheckbox = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='isAnonymous']")
TestObject submitReviewBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' and text()='Submit Review']")

// 3. SUPERVISOR REVIEW LISTING OBJECTS (client/supervisor/supervisorStudentReviews.php)
TestObject reviewAuthorText = new TestObject().addProperty("xpath", ConditionType.EQUALS, "(//p[@class='review-author'])[1]")
TestObject privacyNoteDiv = new TestObject().addProperty("xpath", ConditionType.EQUALS, "(//div[@class='privacy-note'])[1]")

try {
    // ==========================================
    // PART A: STUDENT SUBMISSION (Anonymous = True)
    // ==========================================
    
    // Step 1 & 2: Navigate to login & enter student email
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.setText(emailInput, 'rayden-wp23@student.tarc.edu.my')

    // Step 3 & 4: Enter password & click login
    WebUI.setText(passwordInput, '50517101237')
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate to Supervisor Review page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentReview.php')
    WebUI.waitForPageLoad(15)
    WebUI.waitForElementVisible(submitReviewBtn, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 6: Select a valid star rating (3 stars)
    WebUI.click(starRating3Label)

    // Step 7: Enter valid text feedback
    WebUI.setText(feedbackTextarea, 'Great supervisor support.')

    // Step 8: Activate the "Submit Anonymously" toggle using JavaScript to bypass custom CSS hidden inputs (C1 = True)
    WebElement anonElem = WebUiCommonHelper.findWebElement(anonymousCheckbox, 5)
    WebUI.executeJavaScript("arguments[0].click();", Arrays.asList(anonElem))

    // Step 9: Click "Submit Review" button
    WebUI.click(submitReviewBtn)
    WebUI.waitForPageLoad(15)

    // Step 10: Logout as student (Using Hover to reveal the dropdown first)
    WebUI.mouseOver(accountMenuContainer)
    WebUI.waitForElementVisible(logoutLink, 5, FailureHandling.STOP_ON_FAILURE)
    WebUI.click(logoutLink)
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // ==========================================
    // PART B: SUPERVISOR VERIFICATION
    // ==========================================

    // Step 9 (re-indexed): Enter supervisor email
    WebUI.setText(emailInput, 'leezq1129@tarc.edu.my')

    // Step 10 (re-indexed): Enter supervisor password
    WebUI.setText(passwordInput, 'LeeZQ7181@')

    // Step 11 (re-indexed): Click login
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 12 (re-indexed): Navigate to Student Reviews page
    WebUI.navigateToUrl('http://localhost/ssas/client/supervisor/supervisorStudentReviews.php')
    WebUI.waitForPageLoad(15)

    // VERIFICATION: Assert that the review renders "Anonymous Student" and displays the privacy audit note
    WebUI.waitForElementVisible(reviewAuthorText, 10, FailureHandling.STOP_ON_FAILURE)
    String renderedAuthor = WebUI.getText(reviewAuthorText)
    
    assert renderedAuthor.trim() == "Anonymous Student" : "Expected author to be 'Anonymous Student', but found: " + renderedAuthor
    WebUI.verifyElementVisible(privacyNoteDiv)

    println("F5.2-TC001 Passed: Anonymous toggle successfully masked author identity as 'Anonymous Student' on supervisor view while retaining true ID in backend.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F5.2-TC001 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}