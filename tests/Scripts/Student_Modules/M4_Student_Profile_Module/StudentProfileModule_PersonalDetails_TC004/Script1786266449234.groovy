import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling
import com.kms.katalon.core.webui.common.WebUiCommonHelper
import org.openqa.selenium.WebElement
import java.util.Arrays

// ==============================================================================
// TC004: Verify Personal Details Update (Negative - Invalid URLs)
// Decision Table: C3=F -> Expected = E2 (Intercept & show UI Error)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. PROFILE EDIT OBJECTS
TestObject editProfileBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@id='editProfileBtn']")
TestObject avatarUpload = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='avatarFile']")
TestObject bioTextarea = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//textarea[@id='personalBio']")
TestObject linkedInInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='linkedInURL']")
TestObject githubInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='githubURL']")
TestObject portfolioInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='portfolioURL']")
TestObject saveChangesBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@id='saveProfileBtn']")

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
    WebUI.delay(1)

    // Step 7: Upload VALID avatar (C1 = True)
    String validAvatarPath = "C:\\xampp\\htdocs\\ssas\\tests\\assets\\valid_avatar.jpg"
    WebElement fileInput = WebUiCommonHelper.findWebElement(avatarUpload, 5)
    WebUI.executeJavaScript("arguments[0].style.display = 'block';", Arrays.asList(fileInput))
    WebUI.sendKeys(avatarUpload, validAvatarPath, FailureHandling.STOP_ON_FAILURE)
    
    if (WebUI.verifyAlertPresent(2, FailureHandling.OPTIONAL)) {
        WebUI.acceptAlert()
    }

    // Step 8: Enter valid bio (C2 = True)
    WebUI.setText(bioTextarea, 'Software Engineering final year student passionate about UI/UX and software testing.')

    // Step 9: Inject INVALID URL data (C3 = False)
    // Removed the "htt://" because browsers accept any word followed by "://" as a custom scheme.
    // By providing just the domain or plain text, HTML5 strictly flags it as invalid.
    WebUI.setText(linkedInInput, 'linkedin.com/in/missing-https')
    WebUI.setText(githubInput, 'github.com/missing-protocol')
    WebUI.setText(portfolioInput, 'just-some-random-text')

    // Step 10: Click "Save Changes" button
    WebUI.click(saveChangesBtn)
    
    // VERIFICATION: Check HTML5 validity state directly using JavaScript
    WebElement linkedInElem = WebUiCommonHelper.findWebElement(linkedInInput, 5)
    
    // checkValidity() returns false if the browser natively considers the input format invalid
    Boolean isValid = (Boolean) WebUI.executeJavaScript("return arguments[0].checkValidity();", Arrays.asList(linkedInElem))
    String validationMsg = (String) WebUI.executeJavaScript("return arguments[0].validationMessage;", Arrays.asList(linkedInElem))
    
    if (!isValid) {
        println("SUCCESS: Native HTML5 Validation blocked the form submission.")
        println("Browser Validation Message: " + validationMsg)
        println("TC004 Passed: Invalid URL formats successfully caught by the HTML5 browser constraints.")
    } else {
        throw new Exception("TC004 Failed: Browser HTML5 checkValidity() returned true for an invalid URL string.")
    }

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("TC004 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}