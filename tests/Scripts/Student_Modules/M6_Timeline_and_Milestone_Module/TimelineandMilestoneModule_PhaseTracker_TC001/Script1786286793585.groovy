import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// Function 6.1 - TC001: Verify Phase Tracker (Positive - Server Time Within Phase Bounds)
// Decision Table: C1=T -> Expected = E1 (Render active phase title on widget)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. TIMELINE WIDGET OBJECTS (client/student/studentTimeline.php)
TestObject phaseStatus = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//p[@id='phaseStatus']")
TestObject phaseTitle = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//h2[@id='phaseTitle']")
TestObject phaseMessage = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//p[@id='phaseMessage']")
TestObject countdownValue = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//div[@id='countdownValue']")

try {
    // Step 1: Navigate to login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter student credentials
    WebUI.setText(emailInput, 'rayden-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '50517101237')
    
    // Step 4: Click login
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate directly to the Student Timeline page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentTimeline.php')
    WebUI.waitForPageLoad(15)

    // Step 6: Verify that the Phase Tracker widget elements are visible and populated (C1 = True -> E1)
    WebUI.waitForElementVisible(phaseTitle, 10, FailureHandling.STOP_ON_FAILURE)
    
    String activePhaseName = WebUI.getText(phaseTitle)
    String phaseMsgText = WebUI.getText(phaseMessage)
    String countdownText = WebUI.getText(countdownValue)

    println("Active Phase Detected: " + activePhaseName)
    println("Phase Message: " + phaseMsgText)
    println("Remaining Time: " + countdownText)

    // Assertions to ensure the widget is actively broadcasting valid phase information
    assert activePhaseName != null && !activePhaseName.isEmpty() : "Phase title widget is empty."

    WebUI.verifyElementVisible(phaseStatus)
    WebUI.verifyElementVisible(countdownValue)

    println("F6.1-TC001 Passed: Phase tracker successfully evaluated server time within phase bounds (C1=T) and rendered active phase widget (E1).")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F6.1-TC001 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}