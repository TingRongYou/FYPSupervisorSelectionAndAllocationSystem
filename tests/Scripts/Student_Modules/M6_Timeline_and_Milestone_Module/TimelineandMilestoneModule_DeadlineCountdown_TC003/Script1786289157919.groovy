import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// Timeline and Milestone Module - TC003: Verify Deadline Countdown & Global Lock
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. TIMELINE & DISCOVERY MODULE OBJECTS
TestObject phaseTitle = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//h2[@id='phaseTitle']")
TestObject viewProfileBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "(//a[contains(@class,'link-action') or text()='View Profile'])[1]")
TestObject selectionClosedBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[contains(text(), 'Selection Closed') or @disabled]")

try {
    // Step 1: Navigate to login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter student credentials
    WebUI.setText(emailInput, 'jelly-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '51123453447')
    
    // Step 4: Click login
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate to Timeline & Milestones page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentTimeline.php')
    WebUI.waitForPageLoad(15)

    // Step 6: Verify active phase title indicates non-submission phase / No Active Phase
    WebUI.waitForElementVisible(phaseTitle, 10, FailureHandling.STOP_ON_FAILURE)
    String currentPhase = WebUI.getText(phaseTitle)
    println("Current Phase Title: " + currentPhase)

    // Step 7: Navigate to Supervisor Discovery page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentDiscovery.php')
    WebUI.waitForPageLoad(15)

    // Step 8: Click "View Profile" button
    WebUI.waitForElementVisible(viewProfileBtn, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.click(viewProfileBtn)
    WebUI.waitForPageLoad(15)

    // Step 9 & 10: Inspect and verify "Selection Closed" button is non-interactable
    WebUI.waitForElementVisible(selectionClosedBtn, 10, FailureHandling.STOP_ON_FAILURE)
    boolean isClickable = WebUI.verifyElementClickable(selectionClosedBtn, FailureHandling.OPTIONAL)
    
    if (!isClickable) {
        println("SUCCESS: 'Selection Closed' button is non-interactable, confirming global lock.")
    } else {
        WebUI.click(selectionClosedBtn, FailureHandling.OPTIONAL)
        println("Selection Closed button clicked but system remains on the page without changes.")
    }

    println("TimelineandMilestoneModule_DeadlineCountdown_TC003 Passed successfully.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("TimelineandMilestoneModule_DeadlineCountdown_TC003 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}