import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F3.2 - TC002: Verify Status Tracking (Finalised Proposal State Render)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS (Yong Chong Xin)
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. STATUS TRACKING DASHBOARD & ELEMENT OBJECTS
TestObject summaryGrid = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//section[@class='summary-grid']")
TestObject proposalStatusPanel = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//section[@class='status-panel']")
// Fixed: Use contains(@class, 'badge') to accommodate compound classes like 'badge accepted' or 'badge rejected'
TestObject finalisedBadge = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//span[contains(@class,'badge') and (contains(text(),'Accepted') or contains(text(),'Rejected') or contains(text(),'Withdrawn') or contains(text(),'Auto'))]")
TestObject countdownTimer = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//span[@class='countdown']")

try {
    // Step 1: Navigate to login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter Yong Chong Xin credentials
    WebUI.setText(emailInput, 'yongcx-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, 'Jasden7181@')

    // Step 4: Click login
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate to Application Status page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentApplicationStatus.php')
    WebUI.waitForPageLoad(15)

    // VERIFICATION 1 (E2): Render summary count tiles and proposal table
    WebUI.waitForElementVisible(summaryGrid, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyElementVisible(summaryGrid)
    WebUI.verifyElementVisible(proposalStatusPanel)

    // VERIFICATION 2 (E4): Display finalised badge and ensure countdown timer is omitted
    WebUI.waitForElementVisible(finalisedBadge, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyElementVisible(finalisedBadge)
    WebUI.verifyElementNotPresent(countdownTimer, 5, FailureHandling.STOP_ON_FAILURE)

    println("F3.2-TC002 Passed: Application status page correctly rendered finalised status badge with no countdown timer for yongcx.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F3.2-TC002 Failed: Finalised status tracking rendering failed or countdown timer was incorrectly present.")
    throw e
} finally {
    WebUI.closeBrowser()
}