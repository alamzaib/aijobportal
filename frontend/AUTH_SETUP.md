# Authentication Setup Guide

## Overview
The application uses Laravel Sanctum with HttpOnly cookies for authentication. After login, the UI should automatically update to show the user menu.

## How It Works

1. **Login Flow:**
   - User submits login form
   - API call to `/api/auth/login` with credentials
   - Backend sets HttpOnly cookie with session
   - Frontend updates AuthContext with user data
   - UI automatically updates to show user menu

2. **Auth State Management:**
   - `AuthContext` provides user state across the app
   - Checks authentication on app load via `/api/auth/user`
   - Layout component conditionally renders login/register or user menu

3. **Logout Flow:**
   - Calls `/api/auth/logout` endpoint
   - Clears user state in context
   - Redirects to login page

## Configuration Requirements

### Backend (.env)
```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,localhost:3001,127.0.0.1,127.0.0.1:8000,::1
SESSION_DRIVER=cookie
SESSION_DOMAIN=localhost
```

### Frontend (.env.local)
```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

**Important:** Make sure your frontend port matches one of the domains in `SANCTUM_STATEFUL_DOMAINS`.

## Troubleshooting

### Login succeeds but UI doesn't update

1. **Check browser console** for errors
2. **Verify CORS configuration** - ensure frontend origin is allowed
3. **Check cookies** - open DevTools → Application → Cookies
   - Should see `laravel_session` cookie after login
   - Cookie should be HttpOnly and SameSite=Lax
4. **Verify API URL** - ensure `NEXT_PUBLIC_API_URL` matches backend URL
5. **Check network tab** - verify `/api/auth/user` returns 200 with user data

### Common Issues

**Issue:** Cookies not being set
- **Solution:** Ensure `withCredentials: true` in axios config (already configured)
- Check CORS `Access-Control-Allow-Credentials: true` header
- Verify frontend origin is in `SANCTUM_STATEFUL_DOMAINS`

**Issue:** 401 Unauthorized on `/api/auth/user`
- **Solution:** Check if session cookie is being sent
- Verify CSRF token is being handled (Sanctum handles this automatically)
- Check backend session configuration

**Issue:** CORS errors
- **Solution:** Ensure frontend origin is in CORS allowed origins
- Check `Access-Control-Allow-Origin` header matches frontend URL
- Verify `Access-Control-Allow-Credentials: true` is set

## Testing Authentication

1. **Login:**
   - Go to `/auth/login`
   - Enter credentials
   - Should redirect to home page
   - Header should show user name instead of Login/Register

2. **Check Auth State:**
   - Open browser console
   - Check Network tab for `/api/auth/user` request
   - Should return 200 with user data

3. **Logout:**
   - Click user menu → Logout
   - Should redirect to login page
   - Header should show Login/Register again

## Files Modified

- `contexts/AuthContext.tsx` - Auth state management
- `pages/_app.tsx` - Wraps app with AuthProvider
- `components/Layout.tsx` - Shows user menu when authenticated
- `pages/auth/login.tsx` - Updates auth context on login
- `lib/api.ts` - Handles API calls with credentials

