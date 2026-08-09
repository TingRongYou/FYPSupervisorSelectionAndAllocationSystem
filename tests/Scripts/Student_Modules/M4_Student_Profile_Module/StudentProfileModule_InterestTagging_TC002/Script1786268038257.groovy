import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// Function 4.2 - TC002: Verify Interest Tagging (Negative - Zero Tags Selected)
// Decision Table: C1=F -> Expected = E2 (Intercept & show UI Error)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. PROFILE EDIT OBJECTS
TestObject editProfileBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@id='editProfileBtn']")
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
    
    // Brief stabilization delay to ensure CSS pointer-events blocker is completely removed
    WebUI.delay(1)

    // Step 7: Manage Interest Tags (Select exactly 0 tags)
    // Find all currently selected tags and click them to deselect
    String clearTagsJS = "document.querySelectorAll('label.tag-option.selected').forEach(label => label.click());"
    WebUI.executeJavaScript(clearTagsJS, null)
    
    println("All Research Interest tags have been deselected (Total = 0).")
    WebUI.delay(1)

    // Step 8: Click "Save Changes" button
    WebUI.click(saveChangesBtn)
    
    // VERIFICATION: E2 - Verify the system intercepts the empty tag array and throws an alert
    boolean isAlertPresent = WebUI.waitForAlert(5, FailureHandling.OPTIONAL)
    
    if (isAlertPresent) {
        String alertMsg = WebUI.getAlertText()
        println("SUCCESS: System intercepted 0 tags selected. Alert text: " + alertMsg)
        
        WebUI.acceptAlert()
        println("F4.2-TC002 Passed: Expected UI validation error alert was displayed successfully for C1=False.")
    } else {
        throw new Exception("F4.2-TC002 Failed: Expected UI validation error alert was not displayed when 0 tags were selected.")
    }

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F4.2-TC002 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}