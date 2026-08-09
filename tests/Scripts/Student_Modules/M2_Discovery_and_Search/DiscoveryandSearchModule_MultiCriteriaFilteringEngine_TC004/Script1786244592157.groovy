import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F2.1 - TC004: Verify Multi-Criteria Filter (Research Interest Mismatch)
// ==============================================================================

// 1. OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")
TestObject searchBar = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='searchName']")
TestObject programmeDropdown = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//select[@id='programme']")
TestObject interestDropdown = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//select[@id='interestTagID']")
TestObject applyFiltersBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' and contains(text(),'Apply Filters')]")
TestObject targetSupervisorCard = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//article[contains(@class,'discovery-card') and .//h2[contains(text(), 'Lee Zi Qing')]]")

try {
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.setText(emailInput, 'marshell-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '51117103447')
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentDiscovery.php')

    // Step 6: Enter matching name
    WebUI.setText(searchBar, 'Lee Zi Qing')
    
    // Step 7: Select matching Programme
    WebUI.selectOptionByLabel(programmeDropdown, 'RSW', false, FailureHandling.STOP_ON_FAILURE)
    
    // Step 8: Select a MISMATCHING Research Interest 
    // (Select an interest option that Lee Zi Qing is not tagged with)
    WebUI.selectOptionByIndex(interestDropdown, 7, FailureHandling.STOP_ON_FAILURE) 
    
    // Step 9: Apply Filter
    WebUI.click(applyFiltersBtn)
    WebUI.waitForPageLoad(15)
    
    // VERIFICATION: Verify the card is NOT present
    WebUI.verifyElementNotPresent(targetSupervisorCard, 5, FailureHandling.STOP_ON_FAILURE)
    
    println("F2.1-TC004 Passed: Supervisor filtered out due to Research Interest mismatch.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    throw e
} finally {
    WebUI.closeBrowser()
}