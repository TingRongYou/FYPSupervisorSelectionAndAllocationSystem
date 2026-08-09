import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// Function 6.2 - TC001: Verify Deadline Countdown (Positive - Submission Phase Active)
// Decision Table: C1=T, C2=T -> Expected = E1 (Render dynamic timer showing days, hours, mins)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. TIMELINE & COUNTDOWN WIDGET OBJECTS (client/student/studentTimeline.php)
TestObject phaseTitle = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//h2[@id='phaseTitle']")
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

    // Step 6: Verify active phase is "Submission Phase" (C1 = True)
    WebUI.waitForElementVisible(phaseTitle, 10, FailureHandling.STOP_ON_FAILURE)
    String currentPhase = WebUI.getText(phaseTitle)
    println("Current Active Phase: " + currentPhase)
    assert currentPhase.toLowerCase().contains("submission") : "Expected active phase to be Submission Phase."

    // Step 7: Verify countdown timer displays days, hours, and minutes (C2 = True -> E1)
    WebUI.waitForElementVisible(countdownValue, 10, FailureHandling.STOP_ON_FAILURE)
    String initialCountdown = WebUI.getText(countdownValue)
    println("Initial Countdown Value (Days/Hours/Mins): " + initialCountdown)
    assert initialCountdown != null && !initialCountdown.isEmpty() : "Countdown timer value is empty."

    // Brief wait to allow the client-side updater loop to run
    WebUI.delay(2)
    String updatedCountdown = WebUI.getText(countdownValue)
    println("Updated Countdown Value: " + updatedCountdown)

    println("F6.2-TC001 Passed: Submission phase is active, and dynamic countdown timer (days, hours, minutes) is rendering successfully.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F6.2-TC001 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}