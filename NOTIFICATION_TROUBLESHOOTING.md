# Notification Troubleshooting Guide

## Problem
When staff upload proof of completion for trainings, the admin/head users are not receiving notifications about these uploads.

## Root Cause
The issue is caused by inconsistent role naming in the database and code:

1. In the database, the unit director role is stored as `'unit director'` (with a space)
2. In some parts of the code, the system was looking for `'unit_director'` (with an underscore)
3. This mismatch prevented notifications from being sent to the correct users

## Solution Applied
I've fixed the inconsistency by updating all references to use the correct role name `'unit director'`:

### Files Updated:
1. `training_api.php` - Fixed role check in proof upload notification
2. `update_training_status.php` - Fixed role check in training completion notification
3. `auto_complete_trainings.php` - Fixed role check in auto-completion notification
4. `staff_dashboard.php` - Fixed role check in auto-completion notification

## Verification Steps
To verify the fix is working:

1. **Check Users and Roles**: Run `diagnose_notifications.php` to see all users and their roles
2. **Verify Unit Directors Exist**: Confirm there are users with role `'unit director'`
3. **Test Notification Creation**: The diagnostic script creates a test notification
4. **Check Notifications Table**: Verify notifications appear in the database

## Manual Testing
To manually test the notification system:

1. Log in as a staff member
2. Go to Training Records
3. Select a completed training
4. Upload proof of completion
5. Log in as the unit director
6. Check the Inbox/Notifications - you should see a "Proof Uploaded" notification

## If Issues Persist
If notifications are still not appearing:

1. **Check User Roles**: Ensure the unit director has role `'unit director'` (not `'unit_director'`)
2. **Check Office Codes**: Ensure office heads have the correct `office_code` that matches staff
3. **Check Database Permissions**: Ensure the application can insert into the `notifications` table
4. **Check Error Logs**: Look for PHP errors in the web server error logs

## Cron Job for Auto-Completion
To ensure trainings are automatically marked as completed:

1. Set up a cron job to run `auto_complete_trainings.php` daily
2. This will automatically mark trainings as completed when their end date passes
3. Notifications will be sent when this happens

## Role Reference
Current valid roles in the system:
- `'unit director'` - System administrator
- `'head'` - Office head
- `'staff'` - Regular staff member
- `'unassigned'` - New users awaiting approval

Note: There is no role `'unit_director'` (with underscore) in the system.