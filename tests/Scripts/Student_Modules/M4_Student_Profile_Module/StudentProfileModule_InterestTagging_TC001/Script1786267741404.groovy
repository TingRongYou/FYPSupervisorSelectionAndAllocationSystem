import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling
import com.kms.katalon.core.webui.common.WebUiCommonHelper
import org.openqa.selenium.WebElement
import java.util.Arrays

// ==============================================================================
// Function 4.2 - TC001: Verify Interest Tagging (Positive - Valid Tag Range)
// Decision Table: C1=T, C2=T -> Expected = E1 (Commit data & show Success)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. PROFILE EDIT OBJECTS
TestObject editProfileBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@id='editProfileBtn']")
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
    
    // Brief stabilization delay to ensure CSS pointer-events blocker is completely removed from tags
    WebUI.delay(1)

    // Step 7: Manage Interest Tags (Select exactly 3 tags)
    // First, uncheck any currently selected tags to reset the state
    String clearTagsJS = "document.querySelectorAll('label.tag-option.selected').forEach(label => label.click());"
    WebUI.executeJavaScript(clearTagsJS, null)
    WebUI.delay(1) // Short pause to let UI classes update
    
    // Next, click the first 3 available tags to ensure C1 (>=1) and C2 (<=5) are both TRUE
    String selectThreeTagsJS = """
        let availableTags = document.querySelectorAll('label.tag-option:not(.selected)');
        for(let i = 0; i < 3; i++) {
            if(availableTags[i]) {
                availableTags[i].click();
            }
        }
    """
    WebUI.executeJavaScript(selectThreeTagsJS, null)
    println("3 Research Interest tags have been selected.")

    // Step 8: Click "Save Changes" button
    WebUI.click(saveChangesBtn)
    
    WebUI.waitForPageLoad(15)

    // VERIFICATION: E1 - Verify the success message appears, confirming the array was committed to the DB
    WebUI.waitForElementVisible(successMessage, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyElementVisible(successMessage)

    println("F4.2-TC001 Passed: System successfully allowed the toggle click and committed the valid tag array to the database.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F4.2-TC001 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}