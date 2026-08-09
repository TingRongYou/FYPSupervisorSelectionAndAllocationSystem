import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling
import com.kms.katalon.core.webui.common.WebUiCommonHelper
import org.openqa.selenium.WebElement
import java.util.Arrays

// ==============================================================================
// TC002: Verify Personal Details Update (Negative - Invalid Avatar Type/Size)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. PROFILE EDIT OBJECTS
TestObject editProfileBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@id='editProfileBtn']")
TestObject avatarUpload = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='avatarFile']")

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

    // Step 7: Inject an INVALID file to trigger C1 = False
    String invalidAvatarPath = "C:\\xampp\\htdocs\\ssas\\tests\\assets\\invalid_oversized_avatar.txt"
    
    // Unhide the file input via JS and upload the file natively
    WebElement fileInput = WebUiCommonHelper.findWebElement(avatarUpload, 5)
    WebUI.executeJavaScript("arguments[0].style.display = 'block';", Arrays.asList(fileInput))
    WebUI.sendKeys(avatarUpload, invalidAvatarPath, FailureHandling.STOP_ON_FAILURE)
    
    // VERIFICATION: Check that the client-side JS immediately caught the invalid file and threw the expected alert
    boolean isAlertPresent = WebUI.waitForAlert(5, FailureHandling.OPTIONAL)
    
    if (isAlertPresent) {
        String alertMsg = WebUI.getAlertText()
        println("SUCCESS: System intercepted invalid avatar. Alert text: " + alertMsg)
        
        // Assert the exact alert message defined in student.js
        assert alertMsg.contains("Upload failed. Please ensure your profile picture is in JPG or PNG format") : "Alert message did not match expected UI error."
        
        WebUI.acceptAlert()
        println("TC002 Passed: Expected UI validation error alert was displayed successfully.")
    } else {
        throw new Exception("TC002 Failed: Expected UI validation error alert was not displayed for an invalid avatar.")
    }

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("TC002 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}