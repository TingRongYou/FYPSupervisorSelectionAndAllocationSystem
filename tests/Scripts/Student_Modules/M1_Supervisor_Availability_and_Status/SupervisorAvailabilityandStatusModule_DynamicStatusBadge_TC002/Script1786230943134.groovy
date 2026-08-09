import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F1.2 - TC002: Verify "Full" Status Badge Rendering
// ==============================================================================

// 1. SHARED AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. STUDENT MODULE OBJECTS
TestObject quotaBadge = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//article[contains(@class,'discovery-card') and .//h2[contains(text(), 'Robin')]]//span[contains(@class,'quota-badge')]")

try {
    // Step 1: Navigate to the SSAS login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter student credentials
    WebUI.setText(emailInput, 'yongcx-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, 'Jasden7181@')

    // Step 4: Click the login button
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate explicitly to the Student Discovery page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentDiscovery.php')
    WebUI.waitForElementVisible(quotaBadge, 15, FailureHandling.STOP_ON_FAILURE)

    // Step 6: Verify the Badge Text and CSS Class
    String actualQuotaText = WebUI.getText(quotaBadge)
    String badgeClass = WebUI.getAttribute(quotaBadge, 'class')
    
    // 6a. Verify the text contains "FULL" (Case-sensitive based on UI rendering)
    WebUI.verifyMatch(actualQuotaText, '.*FULL.*', true, FailureHandling.STOP_ON_FAILURE)
    
    // 6b. Verify the HTML element contains the 'full' CSS class (which applies the red color)
    WebUI.verifyMatch(badgeClass, '.*full.*', true, FailureHandling.STOP_ON_FAILURE)
    
    println("F1.2-TC002 Passed: System successfully rendered the red Full badge.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F1.2-TC002 Failed: The badge did not render the correct 'Full' status or CSS class.")
    throw e
} finally {
    WebUI.closeBrowser()
}