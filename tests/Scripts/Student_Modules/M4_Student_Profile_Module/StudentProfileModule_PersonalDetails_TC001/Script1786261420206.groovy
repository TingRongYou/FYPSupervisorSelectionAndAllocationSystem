import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling
import com.kms.katalon.core.webui.common.WebUiCommonHelper
import org.openqa.selenium.WebElement
import java.util.Arrays

// ==============================================================================
// F4.1 - TC001: Verify Personal Details Update (All Validation Conditions Met)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS (Marshell)
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. PROFILE EDIT & FORM OBJECTS
TestObject editProfileBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@id='editProfileBtn']")
TestObject profileForm = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//form[@id='studentProfileForm']")
TestObject avatarUpload = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='avatarFile']")

TestObject bioTextarea = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//textarea[@id='personalBio']")
TestObject contactInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='contactNumber']")
TestObject linkedInInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='linkedInURL']")
TestObject githubInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='githubURL']")
TestObject portfolioInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='portfolioURL']")

// Targets ONLY an unselected tag pill
TestObject unselectedTagLabel = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//label[contains(@class,'tag-option') and not(contains(@class,'selected'))]")

TestObject saveChangesBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@id='saveProfileBtn']")
TestObject successMessage = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//div[@class='message success']")

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

    // Step 5: Navigate directly to Student Profile page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/profile.php')
    WebUI.waitForPageLoad(15)

    // Step 6: Click "Edit Profile" button to unlock form fields
    WebUI.waitForElementVisible(editProfileBtn, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.click(editProfileBtn)

    // Brief stabilization delay to ensure CSS pointer-events blocker is completely removed
    WebUI.delay(1)

    // Step 7: Unhide the file input via JS and upload the file natively
    String validAvatarPath = "C:\\xampp\\htdocs\\ssas\\tests\\assets\\valid_avatar.jpg"
    
    WebElement fileInput = WebUiCommonHelper.findWebElement(avatarUpload, 5)
    WebUI.executeJavaScript("arguments[0].style.display = 'block';", Arrays.asList(fileInput))
    WebUI.sendKeys(avatarUpload, validAvatarPath, FailureHandling.STOP_ON_FAILURE)
    
    // Check if the client-side JS rejected the file (Size > 2MB or invalid format)
    if (WebUI.verifyAlertPresent(2, FailureHandling.OPTIONAL)) {
        String alertMsg = WebUI.getAlertText()
        WebUI.acceptAlert()
        throw new Exception("FILE UPLOAD REJECTED BY JAVASCRIPT: " + alertMsg + " -> Ensure your image is strictly < 2.0MB.")
    }

    // Step 8: Enter valid personal bio
    WebUI.setText(bioTextarea, 'Software Engineering final year student passionate about UI/UX and software testing.')

    // Step 9: Enter valid contact number
    WebUI.setText(contactInput, '012-345 6789')

    // Steps 10-12: Enter valid URLs
    WebUI.setText(linkedInInput, 'https://linkedin.com/in/username')
    WebUI.setText(githubInput, 'https://github.com/username')
    WebUI.setText(portfolioInput, 'https://yourportfolio.com')

    // Step 13: Safely select a NEW (currently unselected) research interest tag
    WebUI.waitForElementVisible(unselectedTagLabel, 5, FailureHandling.STOP_ON_FAILURE)
    WebUI.click(unselectedTagLabel)

    // Step 14: Click "Save Changes" button
    WebUI.waitForElementVisible(saveChangesBtn, 5, FailureHandling.STOP_ON_FAILURE)
    WebUI.click(saveChangesBtn)
    
    WebUI.waitForPageLoad(15)

    // VERIFICATION: Check that success flash message is rendered
    WebUI.waitForElementVisible(successMessage, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyElementVisible(successMessage)

    println("F4.1-TC001 Passed: Profile personal details, avatar upload, and research interests successfully updated and saved.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F4.1-TC001 Failed: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}