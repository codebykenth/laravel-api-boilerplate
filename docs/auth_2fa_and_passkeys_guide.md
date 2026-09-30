# 2FA (TOTP) and Passkeys (WebAuthn) Integration Guide

This guide documents the implementation of Two-Factor Authentication (TOTP) and Passkeys (WebAuthn) for the API Boilerplate backend and its React frontend integration.

---

## 1. Architectural Foundation

Both 2FA and Passkeys integrate into the existing `AuthService` architecture, automatically supporting both authentication modes via `$this->authenticated($user)`:

* **Session Auth (`SessionAuthService`):** Stateful SPA cookie session (`laravel_session` + `XSRF-TOKEN`).
* **Token Auth (`TokenAuthService`):** Stateless Bearer token (`auth_token`).

All endpoints output the consistent envelope defined in `ApiResponse`:
```json
{
  "status": "success",
  "message": "...",
  "data": { ... }
}
```

---

## 2. API Endpoints Reference

### Two-Factor Authentication (TOTP)

| Method | Endpoint | Auth | Purpose | Payload | Response Data |
|---|---|---|---|---|---|
| `POST` | `/api/v1/auth/two-factor-challenge` | Public | Complete 2FA login | `{ "challenge_token": "...", "code": "123456" }` or `{ "recovery_code": "..." }` | `{ "user": { ... }, "token"?: "..." }` |
| `POST` | `/api/v1/auth/two-factor/enable` | Protected | Generate secret & QR | None | `{ "svg": "<svg...", "secret_key": "...", "recovery_codes": [...] }` |
| `POST` | `/api/v1/auth/two-factor/confirm` | Protected | Confirm & activate 2FA | `{ "code": "123456" }` | `null` |
| `DELETE` | `/api/v1/auth/two-factor/disable` | Protected | Deactivate 2FA | None | `null` |
| `GET` | `/api/v1/auth/two-factor/recovery-codes` | Protected | View recovery codes | None | `{ "recovery_codes": [...] }` |
| `POST` | `/api/v1/auth/two-factor/recovery-codes` | Protected | Regenerate codes | None | `{ "recovery_codes": [...] }` |

### Passkeys (WebAuthn)

| Method | Endpoint | Auth | Purpose | Payload | Response Data |
|---|---|---|---|---|---|
| `GET` | `/api/v1/auth/passkeys/options` | Public | Login ceremony challenge | None | `{ "options": { ... }, "challenge_token": "..." }` |
| `POST` | `/api/v1/auth/passkeys/login` | Public | Verify assertion & login | `{ "credential": { ... }, "challenge_token"?: "..." }` | `{ "user": { ... }, "token"?: "..." }` |
| `GET` | `/api/v1/passkeys/options` | Protected | Register ceremony challenge | None | `{ "options": { ... }, "challenge_token": "..." }` |
| `POST` | `/api/v1/passkeys` | Protected | Store passkey credential | `{ "name": "MacBook", "credential": { ... }, "challenge_token"?: "..." }` | Passkey record object |
| `GET` | `/api/v1/passkeys` | Protected | List user passkeys | None | Array of passkey records |
| `DELETE` | `/api/v1/passkeys/{passkey}` | Protected | Revoke a passkey | None | `null` |

---

## 3. Two-Factor Authentication (TOTP) Workflow

### 3.1 Login Flow
```
User Enters Credentials ──► POST /api/v1/auth/login
                                │
                 Is 2FA enabled on user?
                 ├── No  ──► { "user": ..., "token"?: ... } (Logged in)
                 └── Yes ──► { "two_factor": true, "challenge_token": "..." }
                                │
Frontend Displays 2FA Form ─────┘
                                │
Submit Code / Recovery ──► POST /api/v1/auth/two-factor-challenge
                                │
                 Verified? ─────┴──► { "user": ..., "token"?: ... } (Logged in)
```

### 3.2 React Implementation: 2FA Login Form

```tsx
import React, { useState } from 'react';
import axios from 'axios';

interface TwoFactorChallengeProps {
  challengeToken: string;
  onSuccess: (authData: any) => void;
}

export const TwoFactorChallenge: React.FC<TwoFactorChallengeProps> = ({ challengeToken, onSuccess }) => {
  const [useRecovery, setUseRecovery] = useState(false);
  const [code, setCode] = useState('');
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    try {
      const payload = useRecovery
        ? { challenge_token: challengeToken, recovery_code: code }
        : { challenge_token: challengeToken, code: code };

      const res = await axios.post('/api/v1/auth/two-factor-challenge', payload);
      onSuccess(res.data.data);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Invalid two-factor code.');
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <h3>{useRecovery ? 'Two-Factor Recovery Code' : 'Authentication Code'}</h3>
      {error && <div className="text-red-500">{error}</div>}

      <input
        type="text"
        value={code}
        onChange={(e) => setCode(e.target.value)}
        placeholder={useRecovery ? 'Enter recovery code' : '6-digit code'}
        required
      />

      <button type="submit">Verify</button>

      <button type="button" onClick={() => setUseRecovery(!useRecovery)}>
        {useRecovery ? 'Use authenticator app code' : 'Use a recovery code'}
      </button>
    </form>
  );
};
```

### 3.3 React Implementation: Enable 2FA in Settings

```tsx
import React, { useState } from 'react';
import axios from 'axios';

export const TwoFactorSetup: React.FC = () => {
  const [setupData, setSetupData] = useState<{ svg: string; secret_key: string; recovery_codes: string[] } | null>(null);
  const [confirmCode, setConfirmCode] = useState('');
  const [isConfirmed, setIsConfirmed] = useState(false);

  const startEnable = async () => {
    const res = await axios.post('/api/v1/auth/two-factor/enable');
    setSetupData(res.data.data);
  };

  const confirmSetup = async (e: React.FormEvent) => {
    e.preventDefault();
    await axios.post('/api/v1/auth/two-factor/confirm', { code: confirmCode });
    setIsConfirmed(true);
  };

  const disable2FA = async () => {
    await axios.delete('/api/v1/auth/two-factor/disable');
    setSetupData(null);
    setIsConfirmed(false);
  };

  if (isConfirmed) {
    return (
      <div>
        <p className="text-green-600">Two-Factor Authentication is active.</p>
        <button onClick={disable2FA}>Disable 2FA</button>
      </div>
    );
  }

  if (!setupData) {
    return <button onClick={startEnable}>Enable Two-Factor Authentication</button>;
  }

  return (
    <div className="space-y-4">
      <h4>1. Scan QR Code in Authenticator App</h4>
      <div dangerouslySetInnerHTML={{ __html: setupData.svg }} />
      <p>Manual Entry Key: <code>{setupData.secret_key}</code></p>

      <h4>2. Store Recovery Codes</h4>
      <ul>
        {setupData.recovery_codes.map((c) => <li key={c}><code>{c}</code></li>)}
      </ul>

      <form onSubmit={confirmSetup}>
        <input
          type="text"
          value={confirmCode}
          onChange={(e) => setConfirmCode(e.target.value)}
          placeholder="Enter 6-digit code from app"
          required
        />
        <button type="submit">Confirm and Activate</button>
      </form>
    </div>
  );
};
```

---

## 4. Passkeys (WebAuthn) Workflow

WebAuthn uses standard cryptographic ceremonies supported natively by modern browsers (`navigator.credentials`).

### 4.1 Client Package
Install Laravel's official WebAuthn frontend helper:
```bash
npm install @laravel/passkeys
```

### 4.2 React Implementation: Passwordless Login with Passkey

```tsx
import React, { useState } from 'react';
import axios from 'axios';
import { authenticateWithPasskey } from '@laravel/passkeys';

export const PasskeyLoginButton: React.FC<{ onSuccess: (authData: any) => void }> = ({ onSuccess }) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handlePasskeyLogin = async () => {
    setLoading(true);
    setError(null);

    try {
      // 1. Fetch challenge options from API
      const optionsRes = await axios.get('/api/v1/auth/passkeys/options');
      const { options, challenge_token } = optionsRes.data.data;

      // 2. Trigger browser authenticator (TouchID / FaceID / Windows Hello / YubiKey)
      const credential = await authenticateWithPasskey(options);

      // 3. Send signed assertion to backend
      const loginRes = await axios.post('/api/v1/auth/passkeys/login', {
        credential,
        challenge_token,
      });

      onSuccess(loginRes.data.data);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Passkey authentication failed.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <button onClick={handlePasskeyLogin} disabled={loading}>
        {loading ? 'Authenticating...' : 'Sign in with Passkey'}
      </button>
      {error && <p className="text-red-500">{error}</p>}
    </div>
  );
};
```

### 4.3 React Implementation: Passkey Registration in User Settings

```tsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { registerPasskey } from '@laravel/passkeys';

export const PasskeyManagement: React.FC = () => {
  const [passkeys, setPasskeys] = useState<any[]>([]);
  const [name, setName] = useState('');

  const fetchPasskeys = async () => {
    const res = await axios.get('/api/v1/passkeys');
    setPasskeys(res.data.data);
  };

  useEffect(() => {
    fetchPasskeys();
  }, []);

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      // 1. Fetch registration options from API
      const optionsRes = await axios.get('/api/v1/passkeys/options');
      const { options, challenge_token } = optionsRes.data.data;

      // 2. Prompt browser to create passkey
      const credential = await registerPasskey(options);

      // 3. Send public key attestation to API
      await axios.post('/api/v1/passkeys', {
        name,
        credential,
        challenge_token,
      });

      setName('');
      fetchPasskeys();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to create passkey');
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Delete this passkey?')) return;
    await axios.delete(`/api/v1/passkeys/${id}`);
    fetchPasskeys();
  };

  return (
    <div className="space-y-4">
      <h3>Registered Passkeys</h3>
      <ul>
        {passkeys.map((p) => (
          <li key={p.id} className="flex justify-between">
            <span>{p.name} {p.authenticator && `(${p.authenticator})`}</span>
            <button onClick={() => handleDelete(p.id)}>Remove</button>
          </li>
        ))}
      </ul>

      <form onSubmit={handleRegister} className="flex gap-2">
        <input
          type="text"
          value={name}
          onChange={(e) => setName(e.target.value)}
          placeholder="Passkey name (e.g. MacBook Touch ID)"
          required
        />
        <button type="submit">Add Passkey</button>
      </form>
    </div>
  );
};
```

---

## 5. Security & Cross-Origin Configuration

* **`relying_party_id` in `config/passkeys.php`:** Set to your domain (e.g. `localhost` in local development).
* **`allowed_origins` in `config/passkeys.php`:** Must include your React frontend origin (e.g. `http://localhost:5173`).
* **Challenge Expiration:** Challenge tokens for both 2FA and Passkeys are cached with a 5-minute TTL to prevent replay attacks.
