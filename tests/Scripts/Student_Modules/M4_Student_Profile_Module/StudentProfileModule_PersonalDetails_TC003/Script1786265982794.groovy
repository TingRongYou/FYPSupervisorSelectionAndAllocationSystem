import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling
import com.kms.katalon.core.webui.common.WebUiCommonHelper
import org.openqa.selenium.WebElement
import java.util.Arrays

// ==============================================================================
// TC003: Verify Personal Details Update (Negative - Bio Exceeds Limit)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. PROFILE EDIT OBJECTS
TestObject editProfileBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@id='editProfileBtn']")
TestObject avatarUpload = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='avatarFile']")
TestObject bioTextarea = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//textarea[@id='personalBio']")

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

    // Step 8: Inject INVALID Bio data (C2 = False) -> > 500 characters
    // Generates a string of exactly 501 characters ('A' repeated 501 times)
    String overLimitBio = "A" * 501 
    WebUI.setText(bioTextarea, overLimitBio)

    // VERIFICATION: Read the text back from the field to confirm the HTML maxlength attribute truncated it
    // Note: Use getAttribute('value') because getText() might not capture user-inputted text in textareas reliably
    String currentBioText = WebUI.getAttribute(bioTextarea, 'value')
    
    if (currentBioText.length() == 500) {
        println("SUCCESS: Browser natively truncated the 501-character string to 500 characters.")
        println("TC003 Passed: The bio input field correctly restricts input to 500 characters.")
    } else {
        throw new Exception("TC003 Failed: The bio field accepted " + currentBioText.length() + " characters instead of restricting to 500.")
    }

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("TC003 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}