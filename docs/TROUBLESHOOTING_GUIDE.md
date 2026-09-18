# System Troubleshooting Guide
**Issues: Functions work in dev but not for users**

---

## Quick Diagnostics Checklist

### For Users Reporting Issues:
1. **Clear Browser Cache**
   - Press `Ctrl + Shift + Delete`
   - Clear cached images and files
   - Close and reopen browser

2. **Check Browser Console** (Press F12)
   - Look for red errors
   - Common issues:
     - `Content Security Policy` violations
     - `CSRF token mismatch`
     - `404 Not Found` errors
     - `Mixed Content` warnings (HTTP vs HTTPS)

3. **Try Incognito/Private Mode**
   - Rules out browser extensions and cache

---

## Developer Fixes

### 1. Clear All Laravel Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### 2. Fix CSP Nonce Issues

**Problem:** CSP blocks JavaScript when nonce changes between requests.

**Solution A - Add to all cached views:**
```php
// In ContentSecurityPolicy.php, store nonce in session
public function handle($request, Closure $next)
{
    // Use session-based nonce for consistency
    $nonce = session('csp_nonce', base64_encode(random_bytes(16)));
    session(['csp_nonce' => $nonce]);
    
    View::share('cspNonce', $nonce);
    // ... rest of code
}
```

**Solution B - Disable CSP for testing:**
Comment out CSP middleware temporarily in `app/Http/Kernel.php`

### 3. CSRF Token Issues

**Problem:** Forms fail with "419 Page Expired" after session timeout.

**Symptoms:**
- Works initially but fails after 2 hours
- AJAX calls return 419 errors

**Solution:**
```javascript
// Add to all AJAX calls:
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Auto-refresh CSRF token every hour
setInterval(function() {
    $.get('/refresh-csrf').done(function(data) {
        $('meta[name="csrf-token"]').attr('content', data.token);
    });
}, 3600000); // 1 hour
```

**Add route:**
```php
Route::get('/refresh-csrf', function() {
    return response()->json(['token' => csrf_token()]);
});
```

### 4. Environment Configuration

**For Production:**
```env
APP_ENV=production
APP_DEBUG=false  # ← CRITICAL: Hide errors from users
SESSION_LIFETIME=480  # 8 hours instead of 2
```

**After changing .env:**
```bash
php artisan config:cache
php artisan route:cache
```

### 5. JavaScript Error Handling

**Add global error handler:**
```javascript
// Add to main layout
window.onerror = function(msg, url, lineNo, columnNo, error) {
    console.error('JavaScript Error:', {
        message: msg,
        file: url,
        line: lineNo,
        column: columnNo,
        stack: error ? error.stack : ''
    });
    
    // Optional: Send to server for logging
    $.post('/log-js-error', {
        message: msg,
        file: url,
        line: lineNo,
        _token: $('meta[name="csrf-token"]').attr('content')
    });
    
    return false;
};
```

### 6. Asset Compilation

**If using Vite:**
```bash
npm run build
```

**If assets are missing:**
```bash
npm install
npm run build
php artisan storage:link
```

### 7. Permission Issues (Linux/Server)

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 8. Database Connection Issues

**Check:**
- Database server is running
- Credentials in `.env` are correct
- Database name matches
- User has proper permissions

```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## Common User-Specific Issues

### Issue: "Page expired" on form submit
**Cause:** CSRF token expired
**Fix:** Increase `SESSION_LIFETIME` or add token refresh

### Issue: JavaScript not working
**Causes:**
1. CSP blocking scripts (check console for CSP errors)
2. jQuery not loaded
3. Cached old JavaScript
4. Mixed content (HTTPS page loading HTTP scripts)

**Fix:**
```bash
# Clear caches
php artisan view:clear
# Hard refresh browser: Ctrl+Shift+R
```

### Issue: AJAX calls return 419/500 errors
**Causes:**
1. Missing CSRF token
2. Session expired
3. Middleware blocking request

**Debug:**
```javascript
// Add error handler to AJAX calls
$.ajax({
    // ... your config
}).fail(function(xhr) {
    console.error('AJAX Error:', xhr.status, xhr.responseText);
    if (xhr.status === 419) {
        alert('Session expired. Please refresh the page.');
        location.reload();
    }
});
```

### Issue: Styles/layout broken
**Causes:**
1. CSS not loading (check Network tab)
2. Bootstrap not loaded
3. CSP blocking styles

**Fix:**
- Check browser Network tab for 404 errors
- Ensure `npm run build` was executed
- Check CSP allows your CSS sources

### Issue: "Mixed Content" errors (HTTPS)
**Cause:** Loading HTTP resources on HTTPS page
**Fix:** Change all `http://` to `https://` in code

---

## Testing Different User Scenarios

### Test as Different User Roles:
```php
// In browser console or Tinker
Auth::loginUsingId(USER_ID);
```

### Test with Different Browsers:
- Chrome
- Firefox  
- Edge
- Safari (if available)
- Mobile browsers

### Test Network Conditions:
- Chrome DevTools → Network → Throttling
- Test "Slow 3G" to see timeout issues

### Test Session Expiry:
1. Login
2. Wait 2+ hours (or change `SESSION_LIFETIME=1`)
3. Try to submit form
4. Should show proper error handling

---

## Monitoring & Logging

### Enable Error Logging:
```env
# .env
LOG_LEVEL=error
LOG_CHANNEL=daily
```

### Check Logs:
```bash
tail -f storage/logs/laravel.log
```

### Add Custom Logging:
```php
// In problematic functions
Log::info('Student search attempt', ['nic' => $nic]);
Log::error('Failed to update', ['error' => $e->getMessage()]);
```

---

## Production Deployment Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Run `npm run build`
- [ ] Test CSRF tokens work
- [ ] Test in incognito mode
- [ ] Test on different browser
- [ ] Check error logs for issues
- [ ] Verify all AJAX calls include CSRF token
- [ ] Ensure session lifetime is adequate
- [ ] Test with JavaScript disabled (graceful degradation)

---

## Emergency Quick Fixes

### If everything breaks after deployment:
```bash
php artisan down  # Put in maintenance mode
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan up
```

### If CSP blocks everything:
Temporarily disable CSP middleware in `app/Http/Kernel.php`:
```php
// Comment this line:
// \App\Http\Middleware\ContentSecurityPolicy::class,
```

### If sessions keep expiring:
```env
SESSION_LIFETIME=999999
```
Then debug the actual issue.

---

## Contact Support Template

When reporting issues to developers, include:

1. **User Role:** (Admin/Teacher/Student)
2. **Browser:** (Chrome/Firefox/Edge + version)
3. **URL where issue occurs**
4. **Steps to reproduce**
5. **Screenshot of browser console (F12)**
6. **Screenshot of Network tab showing failed requests**
7. **Time of occurrence** (to check logs)

---

**Last Updated:** January 2, 2026
