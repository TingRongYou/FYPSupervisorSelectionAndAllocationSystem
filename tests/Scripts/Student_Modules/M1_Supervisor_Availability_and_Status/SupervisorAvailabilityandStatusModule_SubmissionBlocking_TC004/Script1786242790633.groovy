import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F1.3 - TC005: Block Application Submission (Phase Closed & Max Pending)
// ==============================================================================

// 1. SHARED AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. DISCOVERY MODULE OBJECTS (Target: Lee Zi Qing)
TestObject viewProfileBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//article[contains(@class,'discovery-card') and .//h2[contains(text(), 'Lee Zi Qing')]]//a[contains(text(), 'View Profile')]")

// 3. PROFILE MODULE OBJECTS 
// The disabled span taking the visual place of the button
TestObject disabledActionSpan = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//span[contains(@class,'button') and contains(@class,'disabled')]")

try {
    // Step 1: Navigate to the SSAS login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter student credentials
    WebUI.setText(emailInput, 'marshell-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '51117103447')

    // Step 4: Click the login button
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate explicitly to the Student Discovery page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentDiscovery.php')
    WebUI.waitForElementVisible(viewProfileBtn, 15, FailureHandling.STOP_ON_FAILURE)

    // Step 6: Click "View Profile" for Lee Zi Qing
    WebUI.click(viewProfileBtn)
    WebUI.waitForPageLoad(15)
    
    // Step 7: Inspect the state of the blocked button
    WebUI.waitForElementVisible(disabledActionSpan, 10, FailureHandling.STOP_ON_FAILURE)
    
    // Verify it renders the correct phase-closed text
    String spanText = WebUI.getText(disabledActionSpan)
    WebUI.verifyMatch(spanText, '.*Selection Closed.*|.*Selection Not Open.*|.*Applications Closed.*', true, FailureHandling.STOP_ON_FAILURE)
    
    // Record the current URL before attempting to click
    String preClickUrl = WebUI.getUrl()

    // Step 8: Attempt to click the blocked button
    try {
        WebUI.click(disabledActionSpan, FailureHandling.OPTIONAL)
    } catch (Exception expectedException) {
        println("Click intercepted by the browser. This confirms the element is completely unclickable due to the phase closure!")
    }
    
    // Wait briefly to ensure no navigation triggers
    WebUI.delay(2) 
    
    // Verify the system remains on the detailed supervisor profile page
    String postClickUrl = WebUI.getUrl()
    WebUI.verifyEqual(preClickUrl, postClickUrl, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyNotMatch(postClickUrl, '.*submitProposalForm.*', true, FailureHandling.STOP_ON_FAILURE)
    
    println("F1.3-TC005 Passed: System blocked the pipeline because the phase is closed. Button rendered as '" + spanText + "'.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F1.3-TC005 Failed: The Application pipeline was not properly blocked by the phase check.")
    throw e
} finally {
    WebUI.closeBrowser()
}