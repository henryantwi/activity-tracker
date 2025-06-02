# Manager Role System Implementation

## Overview

The Activity Tracker application now includes a comprehensive three-tier authorization system:
- **Admin**: Full system access with all permissions
- **Manager**: Elevated permissions to oversee team activities and generate reports
- **User**: Standard access limited to their own assigned/created activities

## Role Hierarchy

```
Admin (highest)
  ├── Can manage all users and activities
  ├── Can access all system features
  ├── Can search and view all activities
  ├── Can generate and export reports
  └── Can delete any activity

Manager (middle)
  ├── Can search and view all activities
  ├── Can generate and export reports  
  ├── Can update any activity
  ├── Can delete activities they created
  └── Cannot manage users or system settings

User (basic)
  ├── Can only view activities assigned to them or created by them
  ├── Can update activities assigned to them or created by them
  ├── Can delete activities they created
  └── Cannot access reports or search all activities
```

## Implementation Details

### Database Schema

The `users` table includes a `role` column:
```sql
role ENUM('admin', 'manager', 'user') DEFAULT 'user'
```

### User Model Methods

The `User` model includes role-checking methods:

```php
// Role checks
public function isAdmin(): bool
public function isManager(): bool  
public function isUser(): bool

// Permission checks
public function canSearchAllActivities(): bool
public function canManageReports(): bool
```

### Authorization Logic

#### ActivityController Authorization

1. **View/Index Activities**: 
   - Admins and managers: Can see all activities
   - Users: Can only see activities assigned to them or created by them

2. **Update Activities**:
   - Admins and managers: Can update any activity
   - Users: Can only update activities assigned to them or created by them

3. **Delete Activities**:
   - Admins and managers: Can delete any activity (managers can delete activities they created)
   - Users: Can only delete activities they created

#### ReportController Authorization

Reports are restricted to admins and managers only:
```php
public function __construct()
{
    $this->middleware(function ($request, $next) {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isManager())) {
            abort(403, 'You are not authorized to access reports');
        }
        return $next($request);
    });
}
```

### UI/UX Changes

#### Navigation Menu

The Reports menu item is now only visible to admins and managers:
```php
@if(auth()->user()->isAdmin() || auth()->user()->isManager())
<li class="nav-item">
    <a class="nav-link" href="{{ route('reports.index') }}">
        <i class="fas fa-chart-bar me-1"></i>Reports
    </a>
</li>
@endif
```

#### Dashboard Features

Admin-only sections in the dashboard are now accessible to managers as well:
```php
@if((auth()->user()->isAdmin() || auth()->user()->isManager()) && $pendingHandovers->count() > 0)
```

### Form Request Validation

Both `UpdateActivityRequest` and `StoreActivityUpdateRequest` include manager authorization:

```php
public function authorize()
{
    $activity = $this->route('activity');
    $user = Auth::user();
    
    return $user->isAdmin() ||
           $user->isManager() ||
           $activity->created_by === Auth::id() ||
           $activity->assigned_to === Auth::id();
}
```

## Testing the Implementation

### Current Test Data

The system includes test users with different roles:
- **Henry Antwi** (henry@email.com): Admin
- **Nana** (henry@nesttop.tech): Manager  
- **Test Manager** (manager@test.com): Manager
- **Test User** (user@test.com): User

### Verification Steps

1. **Login as Manager**: Verify access to reports and ability to search all activities
2. **Login as User**: Confirm restricted access to own activities only
3. **Check Navigation**: Ensure reports menu only shows for admins and managers
4. **Test Activity Operations**: Verify authorization for view, edit, delete operations
5. **Report Access**: Confirm managers can generate and export reports

## Features Available to Managers

### Activity Management
- ✅ Search and view all activities (not just assigned ones)
- ✅ Update any activity status and details
- ✅ Quick status updates on any activity
- ✅ View detailed activity history and updates

### Reporting & Analytics
- ✅ Access to comprehensive reports dashboard
- ✅ Generate activity reports by date range
- ✅ Export reports in various formats
- ✅ View system-wide activity statistics

### Team Oversight
- ✅ Monitor team activity progress
- ✅ View pending handovers (admin/manager section)
- ✅ Track overdue activities across all users
- ✅ Access performance metrics and trends

## Security Considerations

1. **Explicit Authorization**: All controller methods include explicit authorization checks
2. **Form Request Validation**: Request classes validate permissions at the form level
3. **UI Restrictions**: Menu items and features are conditionally displayed based on roles
4. **Middleware Protection**: Reports controller uses middleware for role-based access control

## Future Enhancements

Potential manager-specific features to consider:
- Team assignment management
- Bulk activity operations
- Manager-specific dashboard widgets
- Team performance reporting
- Activity delegation capabilities
- Notification preferences for team activities

## Troubleshooting

### Common Issues

1. **403 Errors**: Check user role assignment and authorization logic
2. **Missing Menu Items**: Verify role-based UI conditionals
3. **Report Access Denied**: Ensure user has manager or admin role
4. **Activity Visibility**: Check `canSearchAllActivities()` method implementation
