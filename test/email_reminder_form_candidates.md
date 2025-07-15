# Super Forms Email Reminder Testing Candidates

Based on the analysis of all form exports, I found 22 forms with email reminder configurations. Here are the best candidates for testing email reminders migration functionality:

## Top Recommended Forms for Testing

### 1. **Form 69852 - "Translated Form"** (BEST CANDIDATE)
- **File**: `form_69852.json`
- **Why this is the best**: This form has the most comprehensive email reminder configuration
- **Features**:
  - Has `email_reminder_1: true` (explicitly enabled)
  - Custom email settings (from: `no-reply@f4d.nl`, reply-to: `sales@f4d.nl`)
  - Custom subject: "Thank you for contacting our company :)"
  - Advanced features: CC, BCC, custom headers, attachments
  - RTL support enabled
  - Exclude empty fields enabled

### 2. **Form 142 - "File Upload"**
- **File**: `form_142.json`
- **Why it's good**: Has all three email reminders configured (1, 2, and 3)
- **Features**:
  - All 3 email reminders have full configuration
  - Standard email settings with placeholders
  - Good for testing migration of multiple reminders

### 3. **Form 52047 - "Checkbox/Radio Slider"**
- **File**: `form_52047.json`
- **Why it's good**: Another form with all 3 email reminders configured
- **Features**:
  - All 3 email reminders configured
  - Contains both body_open and body content
  - Good for testing complex form elements with reminders

### 4. **Form 49269 - "Google reCAPTCHA (Copy)"**
- **File**: `form_49269.json`
- **Features**:
  - Uses `{field_email}` instead of `{email}` in recipient
  - Has 2 email reminders configured
  - Good for testing different email field references

### 5. **Form 53093 - "Google Maps"**
- **File**: `form_53093.json`
- **Features**:
  - All 3 email reminders configured
  - Good for testing with complex form elements

## Email Reminder Settings Found

All forms contain similar settings structure:
- `email_reminder_X_date_offset`: Usually "0"
- `email_reminder_X_time_method`: "fixed"
- `email_reminder_X_time_fixed`: "09:00"
- `email_reminder_X_to`: Email recipient (varies: `{email}`, `{field_email}`, specific emails)
- `email_reminder_X_from_type`: "default" or "custom"
- `email_reminder_X_subject`: Email subject line
- `email_reminder_X_body_open`: Opening text
- `email_reminder_X_body_close`: Closing text
- `email_reminder_X_email_loop`: HTML template for field loops

## Special Features Found

### Form 69852 has unique advanced features:
- **Email enabled flag**: `email_reminder_1: true`
- **Custom headers**: `Custom-Header: something, Custom-Header2: something2`
- **CC/BCC**: Multiple CC and BCC email addresses
- **Attachments**: Reference to attachment ID `65502`
- **RTL support**: `email_reminder_1_rtl: true`
- **Exclude empty**: `email_reminder_1_exclude_empty: true`

## Complete List of Forms with Email Reminders

1. Form 142 - File Upload
2. Form 52047 - Checkbox/Radio Slider
3. Form 69852 - Translated Form ⭐ **BEST CANDIDATE**
4. Form 49269 - Google reCAPTCHA (Copy)
5. Form 49490 - Form Name
6. Form 53093 - Google Maps
7. Form 3265 - Validate Previous Value
8. Form 852 - Tooltips
9. Form 28223 - Register (Copy)
10. Form 52044 - TABS Element
11. Form 3271 - Dynamic URL Redirect Parameters
12. Form 3258 - Advanced Datepickers
13. Form 56265 - Contact landing
14. Form 53289 - Form Name
15. Form 3252 - Form Name
16. Form 3273 - Form Name
17. Form 69618 - Form Name
18. Form 3285 - Validate Previous Value (Alternative)
19. Form 3376 - Form Name
20. Form 1331 - Signature (Add-on)
21. Form 26014 - Booking Form
22. Form 925 - Dynamic Columns

## Recommendation

**Use Form 69852 ("Translated Form")** as the primary test case because:
1. It has the most comprehensive email reminder configuration
2. It includes advanced features like custom headers, CC/BCC, and attachments
3. It has explicit enablement flag (`email_reminder_1: true`)
4. It represents a real-world, complex use case
5. It will thoroughly test the migration functionality

**Use Form 142 ("File Upload")** as a secondary test case because:
1. It has all three email reminders configured
2. It's simpler and good for basic migration testing
3. It uses standard configurations that are common across many forms