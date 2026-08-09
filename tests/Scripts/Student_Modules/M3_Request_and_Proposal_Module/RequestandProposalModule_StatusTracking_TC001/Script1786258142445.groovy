import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F3.2 - TC001: Verify Status Tracking (Pending Proposal State Render)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS (Marshell)
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. STATUS TRACKING DASHBOARD & ELEMENT OBJECTS (Based on client/student/studentApplicationStatus.php)
TestObject summaryGrid = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//section[@class='summary-grid']")
TestObject proposalStatusPanel = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//section[@class='status-panel']")
TestObject pendingBadge = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//span[@class='badge pending' and contains(text(),'Pending')]")
TestObject countdownTimer = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//span[@class='countdown' and @data-expiry]")

try {
    // Step 1: Navigate to login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter credentials
    WebUI.setText(emailInput, 'marshell-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '51117103447')

    // Step 4: Click login
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate to Application Status page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentApplicationStatus.php')
    WebUI.waitForPageLoad(15)

    // VERIFICATION 1 (E2): Render the summary count tiles and proposal status panel table
    WebUI.waitForElementVisible(summaryGrid, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyElementVisible(summaryGrid)
    WebUI.verifyElementVisible(proposalStatusPanel)

    // VERIFICATION 2 (E3): Display Yellow "Pending" badge and live TTL countdown timer element with expiry attribute
    WebUI.verifyElementVisible(pendingBadge)
    WebUI.verifyElementVisible(countdownTimer)
    
    // Optional check: Ensure data-expiry contains a valid timestamp string
    String expiryVal = WebUI.getAttribute(countdownTimer, "data-expiry")
    if (expiryVal == null || expiryVal.isEmpty()) {
        throw new Exception("Countdown timer is missing its valid data-expiry timestamp attribute.")
    }

    println("F3.2-TC001 Passed: Application status panel, count tiles, pending badge, and active countdown timer are correctly rendered.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F3.2-TC001 Failed: Pending status tracking elements were not rendered correctly.")
    throw e
} finally {
    WebUI.closeBrowser()
}