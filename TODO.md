# TODO for Security Enhancements

## Strong Password Requirements in Register Page
- [x] Add password strength validation function
- [x] Update register.php to check password requirements
- [x] Display password requirements to user
- [ ] Test registration with weak passwords

## Login Attempt Limit in Login Page
- [x] Add login attempt tracking (session or database)
- [x] Implement 5 attempt limit with 2-minute cooldown
- [x] Update login.php with attempt logic
- [ ] Test login attempts and cooldown

## Database Updates
- [x] Check if database schema needs updates for attempt tracking
- [x] Update database.sql if necessary

## Testing
- [ ] Test overall functionality
- [ ] Ensure no breaking changes
