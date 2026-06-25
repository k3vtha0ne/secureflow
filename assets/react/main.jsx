import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';

const API_BASE_URL = '';

function extractCollection(payload) {
  return payload.member ?? payload['hydra:member'] ?? [];
}

async function apiFetch(path, token, options = {}) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers: {
      Accept: 'application/ld+json',
      ...(options.headers ?? {}),
      Authorization: `Bearer ${token}`,
    },
  });

  if (!response.ok) {
    throw new Error(`API error ${response.status}`);
  }

  return response.json();
}

function LoginForm({ onLogin }) {
  const [email, setEmail] = useState('admin@alpha.test');
  const [password, setPassword] = useState('password');
  const [error, setError] = useState('');
  const [isSubmitting, setSubmitting] = useState(false);

  async function handleSubmit(event) {
    event.preventDefault();

    setError('');
    setSubmitting(true);

    try {
      const response = await fetch('/api/login_check', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });

      if (!response.ok) {
        throw new Error('Invalid credentials');
      }

      const payload = await response.json();

      if (!payload.token) {
        throw new Error('Missing JWT token');
      }

      localStorage.setItem('secureflow_token', payload.token);
      onLogin(payload.token);
    } catch (error) {
      setError(error.message);
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <main className="page">
      <section className="login-card">
        <p className="eyebrow">SecureFlow</p>
        <h1>API Dashboard</h1>
        <p className="muted">
          Sign in with a test user to load secured documents and campaigns.
        </p>

        <form onSubmit={handleSubmit} className="form">
          <label>
            Email
            <input
              type="email"
              value={email}
              autoComplete="username"
              onChange={(event) => setEmail(event.target.value)}
            />
          </label>

          <label>
            Password
            <input
              type="password"
              value={password}
              autoComplete="current-password"
              onChange={(event) => setPassword(event.target.value)}
            />
          </label>

          {error && <p className="error">{error}</p>}

          <button type="submit" disabled={isSubmitting}>
            {isSubmitting ? 'Signing in...' : 'Sign in'}
          </button>
        </form>
      </section>
    </main>
  );
}

function ResourceList({ title, items, labelKey, emptyMessage }) {
  return (
    <section className="panel">
      <div className="panel-header">
        <h2>{title}</h2>
        <span>{items.length}</span>
      </div>

      {items.length === 0 ? (
        <p className="muted">{emptyMessage}</p>
      ) : (
        <ul className="resource-list">
          {items.map((item) => (
            <li key={item.id ?? item['@id']}>
              <strong>{item[labelKey]}</strong>
              {item.status && <span>{item.status}</span>}
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}

function Dashboard({ token, onLogout }) {
  const [documents, setDocuments] = useState([]);
  const [campaigns, setCampaigns] = useState([]);
  const [error, setError] = useState('');
  const [isLoading, setLoading] = useState(true);

  useEffect(() => {
    async function loadDashboard() {
      setError('');
      setLoading(true);

      try {
        const [documentsPayload, campaignsPayload] = await Promise.all([
          apiFetch('/api/documents', token),
          apiFetch('/api/campaigns', token),
        ]);

        setDocuments(extractCollection(documentsPayload));
        setCampaigns(extractCollection(campaignsPayload));
      } catch (error) {
        setError(error.message);
      } finally {
        setLoading(false);
      }
    }

    loadDashboard();
  }, [token]);

  return (
    <main className="page">
      <header className="topbar">
        <div>
          <p className="eyebrow">SecureFlow</p>
          <h1>Dashboard</h1>
        </div>

        <button type="button" className="secondary-button" onClick={onLogout}>
          Logout
        </button>
      </header>

      {isLoading && <p className="muted">Loading API resources...</p>}
      {error && <p className="error">Unable to load dashboard: {error}</p>}

      {!isLoading && !error && (
        <div className="grid">
          <ResourceList
            title="Documents"
            items={documents}
            labelKey="title"
            emptyMessage="No document available."
          />

          <ResourceList
            title="Campaigns"
            items={campaigns}
            labelKey="name"
            emptyMessage="No campaign available."
          />
        </div>
      )}
    </main>
  );
}

function App() {
  const [token, setToken] = useState(() => localStorage.getItem('secureflow_token'));

  function handleLogout() {
    localStorage.removeItem('secureflow_token');
    setToken(null);
  }

  if (!token) {
    return <LoginForm onLogin={setToken} />;
  }

  return <Dashboard token={token} onLogout={handleLogout} />;
}

createRoot(document.getElementById('root')).render(<App />);
