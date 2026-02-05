# 2FA Setup Flow - Test Checklist

## Pre-Testing Setup
- [ ] Pull latest changes from git: `git pull origin main`
- [ ] Clear application cache: `php artisan cache:clear`
- [ ] Clear config cache: `php artisan config:clear`
- [ ] Clear view cache: `php artisan view:clear`
- [ ] Clear route cache: `php artisan route:clear`
- [ ] Ensure you have an authenticator app installed (Google Authenticator, Microsoft Authenticator, Authy, or 1Password)

---

## Test Scenario 1: Admin User Exceeds Login Limit (Forced 2FA Setup)

### Setup Steps:
1. [ ] Log in as an admin/owner/developer user
2. [ ] Navigate to the database and set `logins_without_2fa` to a value >= 10 (or whatever `MAX_LOGINS_WITHOUT_2FA` is set to)
   ```sql
   UPDATE users SET logins_without_2fa = 10 WHERE email = 'your-admin@email.com';
   ```
3. [ ] Log out and log back in

### Expected Behavior:
- [ ] User is automatically redirected to `/settings/two-factor` page
- [ ] A red warning banner appears stating "Two-Factor Authentication Required"
- [ ] The QR code modal **automatically appears** without clicking any button
- [ ] Modal shows:
  - [ ] Step-by-step instructions (Step 1, 2, 3)
  - [ ] List of recommended authenticator apps (Google Authenticator, Microsoft Authenticator, Authy, 1Password)
  - [ ] QR code displayed clearly
  - [ ] Manual setup key displayed
  - [ ] Warning message: "⚠️ Important: On your next login, you will be required to enter the 6-digit code from your authenticator app."
- [ ] **Modal cannot be closed** by clicking outside (backdrop click disabled)
- [ ] No close button visible on the modal

### Test Actions:
- [ ] Try clicking outside the modal (should NOT close)
- [ ] Try pressing ESC key (should NOT close - if implemented)
- [ ] Scan the QR code with your authenticator app
- [ ] Verify the app shows a 6-digit code for your account
- [ ] Click "Continue" button
- [ ] Verify the verification step appears

---

## Test Scenario 2: Verification Step

### Expected Behavior:
- [ ] Modal title changes to "Verify Authentication Code"
- [ ] Instructions appear: "Open your authenticator app and enter the 6-digit code shown there. This code refreshes every 30 seconds."
- [ ] Input field is focused and ready for code entry
- [ ] Warning message appears: "⚠️ Remember: On your next login, you will be required to enter a code from your authenticator app to access your account."
- [ ] "Back" button returns to QR code view
- [ ] "Confirm" button submits the code

### Test Actions:
- [ ] Enter an **incorrect** code (e.g., 000000)
  - [ ] Should show validation error
- [ ] Enter the **correct** 6-digit code from your authenticator app
  - [ ] Should successfully verify
  - [ ] Modal should close
  - [ ] Success message should appear (if implemented)
  - [ ] User should see "Two-factor authentication is enabled" message
  - [ ] `logins_without_2fa` counter should be reset to 0 in database

---

## Test Scenario 3: Next Login - 2FA Code Required

### Setup Steps:
1. [ ] After completing 2FA setup, log out completely
2. [ ] Clear browser session/cookies (or use incognito/private window)

### Expected Behavior:
- [ ] Log in with email and password
- [ ] After successful password authentication, user is redirected to 2FA challenge page
- [ ] Page prompts for 6-digit code from authenticator app
- [ ] User cannot proceed without entering valid code

### Test Actions:
- [ ] Enter **incorrect** code
  - [ ] Should show error message
  - [ ] Should allow retry
- [ ] Enter **correct** code from authenticator app
  - [ ] Should successfully log in
  - [ ] Should redirect to dashboard or intended destination

---

## Test Scenario 4: Manual 2FA Setup (Not Forced)

### Setup Steps:
1. [ ] Log in as an admin user with `logins_without_2fa` < 10
2. [ ] Navigate to Settings → Two Factor Authentication manually

### Expected Behavior:
- [ ] Page loads normally
- [ ] Warning banner shows remaining logins (if applicable)
- [ ] QR code modal does **NOT** auto-open
- [ ] User must click "Enable Two-Factor Authentication" button
- [ ] After clicking, modal opens with QR code
- [ ] Modal **CAN be closed** by clicking outside (backdrop click enabled)
- [ ] All instructions are visible and clear

### Test Actions:
- [ ] Click "Enable Two-Factor Authentication" button
- [ ] Verify modal opens
- [ ] Try clicking outside modal (should close)
- [ ] Re-open modal and complete setup

---

## Test Scenario 5: Instructions Visibility

### Check Main Page:
- [ ] "Recommended Authenticator Apps" section is visible
- [ ] List shows: Google Authenticator, Microsoft Authenticator, Authy, 1Password
- [ ] "How it works" explanation is visible
- [ ] Message states: "On your next login and every login after that, you'll be required to enter the 6-digit code..."

### Check QR Code Modal:
- [ ] Step 1 instructions visible (Install app)
- [ ] Step 2 instructions visible (Scan QR code)
- [ ] Step 3 instructions visible (Verify setup)
- [ ] Warning about next login requirement is prominent

### Check Verification Step:
- [ ] Instructions about where to find code are clear
- [ ] Warning about next login requirement is visible
- [ ] Code refreshes every 30 seconds note is present

---

## Test Scenario 6: Local Environment Bypass (If Applicable)

### Setup Steps:
1. [ ] Verify `.env` has `APP_ENV=local` or `APP_ENV=development`
2. [ ] Log in as admin user

### Expected Behavior:
- [ ] 2FA enforcement middleware should be bypassed
- [ ] User should NOT be redirected to 2FA setup page
- [ ] User can access admin areas without 2FA

### Test Actions:
- [ ] Set `logins_without_2fa` to 10+ in database
- [ ] Log out and log back in
- [ ] Should NOT be redirected to 2FA setup
- [ ] Should access dashboard normally

---

## Test Scenario 7: Edge Cases

### Test Invalid Code:
- [ ] Enter code with less than 6 digits
  - [ ] Should show validation error
- [ ] Enter code with letters
  - [ ] Should show validation error
- [ ] Enter expired code (wait 30+ seconds after scanning)
  - [ ] Should show error (if code expired)

### Test Modal Behavior:
- [ ] If forced to set up, try navigating away from page
  - [ ] Should redirect back to 2FA setup (if middleware enforces)
- [ ] Complete setup, then try to disable 2FA
  - [ ] Admin/owner users should NOT be able to disable (if enforced)

---

## Test Scenario 8: Security Questions Requirement (If Applicable)

### For Regular Members:
- [ ] If user doesn't have security questions set up
- [ ] "Enable Two-Factor Authentication" button should be disabled
- [ ] Message should explain requirement
- [ ] User must set up security questions first

### For Admin/Owner:
- [ ] Should be able to enable 2FA without security questions
- [ ] Button should be enabled

---

## Post-Testing Verification

### Database Checks:
- [ ] Verify `two_factor_secret` is encrypted in database
- [ ] Verify `two_factor_confirmed_at` is set after verification
- [ ] Verify `logins_without_2fa` is reset to 0 after setup
- [ ] Verify recovery codes are generated (if applicable)

### User Experience:
- [ ] All instructions are clear and easy to follow
- [ ] QR code is clearly visible and scannable
- [ ] Manual setup key is readable
- [ ] Error messages are helpful
- [ ] Success messages are clear

---

## Issues Found

### Critical Issues:
- [ ] List any critical bugs that prevent 2FA setup

### Minor Issues:
- [ ] List any UI/UX improvements needed

### Suggestions:
- [ ] List any enhancement ideas

---

## Notes
- Test on both desktop and mobile browsers
- Test with different authenticator apps
- Verify the flow works on production environment (not just local)
- Check that all text is properly translated (if multi-language)

---

## Quick Test Commands

```bash
# Reset a user's 2FA counter for testing
php artisan tinker
>>> $user = App\Models\User::where('email', 'test@example.com')->first();
>>> $user->update(['logins_without_2fa' => 10]);

# Check if 2FA is enabled
>>> $user->hasEnabledTwoFactorAuthentication();

# Reset 2FA for a user (if needed)
>>> $user->two_factor_secret = null;
>>> $user->two_factor_confirmed_at = null;
>>> $user->two_factor_recovery_codes = null;
>>> $user->save();
```

---

**Test Date:** _______________  
**Tester:** _______________  
**Environment:** _______________ (local/staging/production)  
**Browser:** _______________  
**Authenticator App Used:** _______________
