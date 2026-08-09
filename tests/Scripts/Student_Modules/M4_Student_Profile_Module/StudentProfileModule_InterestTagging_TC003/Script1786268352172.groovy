import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// Function 4.2 - TC003: Verify Interest Tagging (Negative - Exceeds 5 Tags Limit)
// Decision Table: C1=T, C2=F -> Expected = E2 (Rollback toggle & show Alert)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. PROFILE EDIT OBJECTS
TestObject editProfileBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@id='editProfileBtn']")

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

    // Step 7: Clear all active tags first to ensure a known starting state
    String clearTagsJS = "document.querySelectorAll('label.tag-option.selected').forEach(label => label.click());"
    WebUI.executeJavaScript(clearTagsJS, null)
    WebUI.delay(1)

    // Select exactly 5 tags (the maximum allowed boundary)
    String selectFiveTagsJS = """
        let availableTags = document.querySelectorAll('label.tag-option:not(.selected)');
        for(let i = 0; i < 5; i++) {
            if(availableTags[i]) {
                availableTags[i].click();
            }
        }
    """
    WebUI.executeJavaScript(selectFiveTagsJS, null)
    println("5 Research Interest tags successfully selected (Maximum boundary reached).")

    // Step 8: Attempt to click a 6th tag to trigger C2 = False (> 5 tags)
    String clickSixthTagJS = """
        let availableTags = document.querySelectorAll('label.tag-option:not(.selected)');
        if(availableTags.length > 0) {
            availableTags[0].click();
        }
    """
    WebUI.executeJavaScript(clickSixthTagJS, null)

    // VERIFICATION: E2 - Check that native browser alert was triggered instantly upon selecting 6th tag
    boolean isAlertPresent = WebUI.waitForAlert(5, FailureHandling.OPTIONAL)
    
    if (isAlertPresent) {
        String alertMsg = WebUI.getAlertText()
        println("SUCCESS: System intercepted 6th tag selection. Alert text: " + alertMsg)
        
        WebUI.acceptAlert()
        
        // Verify that the total selected tags was rolled back and remained strictly 5
        Long selectedCount = (Long) WebUI.executeJavaScript("return document.querySelectorAll('label.tag-option.selected').length;", null)
        
        if (selectedCount == 5) {
            println("SUCCESS: System successfully rolled back the toggle state. Current selected tags count: " + selectedCount)
            println("F4.2-TC003 Passed: Expected UI alert displayed and toggle rolled back for C2=False.")
        } else {
            throw new Exception("F4.2-TC003 Failed: Selected tag count was " + selectedCount + " instead of rolling back to 5.")
        }
    } else {
        throw new Exception("F4.2-TC003 Failed: Expected UI validation error alert was not displayed when attempting to select a 6th tag.")
    }

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F4.2-TC003 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}