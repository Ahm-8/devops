import React, { useState } from 'react';
import { signIn, signUp, confirmSignUp, resendConfirmationCode } from '../services/auth';
import { useNavigate } from 'react-router-dom';
import './Auth.css';

const Auth = () => {
  const [isSignUp, setIsSignUp] = useState(false);
  const [needsConfirmation, setNeedsConfirmation] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [name, setName] = useState('');
  const [confirmationCode, setConfirmationCode] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      if (needsConfirmation) {
        await confirmSignUp(email, confirmationCode);
        setSuccess('Email verified! You can now sign in.');
        setNeedsConfirmation(false);
        setIsSignUp(false);
        setConfirmationCode('');
      } else if (isSignUp) {
        await signUp(email, password, name);
        setSuccess('Account created! Please check your email for the verification code.');
        setNeedsConfirmation(true);
      } else {
        await signIn(email, password);
        setSuccess('Signed in successfully!');
        setTimeout(() => navigate('/rooms'), 500);
      }
    } catch (err) {
      setError(err.message || 'An error occurred');
    } finally {
      setLoading(false);
    }
  };

  const handleResendCode = async () => {
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      await resendConfirmationCode(email);
      setSuccess('Verification code resent! Check your email.');
    } catch (err) {
      setError(err.message || 'Failed to resend code');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <h2>
          {needsConfirmation
            ? 'Verify Email'
            : isSignUp
            ? 'Sign Up'
            : 'Sign In'}
        </h2>
        <form onSubmit={handleSubmit}>
          {needsConfirmation ? (
            <>
              <div className="form-group">
                <label>Verification Code</label>
                <input
                  type="text"
                  value={confirmationCode}
                  onChange={(e) => setConfirmationCode(e.target.value)}
                  required
                  placeholder="Enter 6-digit code from email"
                  maxLength="6"
                />
              </div>
              <div className="info-message">
                Code sent to: {email}
              </div>
            </>
          ) : (
            <>
              {isSignUp && (
                <div className="form-group">
                  <label>Name</label>
                  <input
                    type="text"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    required
                    placeholder="Enter your name"
                  />
                </div>
              )}
              <div className="form-group">
                <label>Email</label>
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                  placeholder="Enter your email"
                />
              </div>
              <div className="form-group">
                <label>Password</label>
                <input
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                  placeholder="Enter your password"
                  minLength="8"
                />
              </div>
            </>
          )}
          {error && <div className="error-message">{error}</div>}
          {success && <div className="success-message">{success}</div>}
          <button type="submit" disabled={loading} className="submit-btn">
            {loading
              ? 'Loading...'
              : needsConfirmation
              ? 'Verify Email'
              : isSignUp
              ? 'Sign Up'
              : 'Sign In'}
          </button>
        </form>
        {needsConfirmation ? (
          <div className="toggle-auth">
            Didn't receive the code?{' '}
            <button onClick={handleResendCode} className="toggle-btn" disabled={loading}>
              Resend Code
            </button>
          </div>
        ) : (
          <div className="toggle-auth">
            {isSignUp ? 'Already have an account?' : "Don't have an account?"}{' '}
            <button onClick={() => setIsSignUp(!isSignUp)} className="toggle-btn">
              {isSignUp ? 'Sign In' : 'Sign Up'}
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default Auth;
