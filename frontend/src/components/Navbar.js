import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { signOut } from '../services/auth';
import './Navbar.css';

const Navbar = () => {
  const navigate = useNavigate();

  const handleSignOut = () => {
    signOut();
    navigate('/');
  };

  return (
    <nav className="navbar">
      <div className="nav-container">
        <Link to="/rooms" className="nav-logo">
          Conference Booking
        </Link>
        <ul className="nav-menu">
          <li>
            <Link to="/rooms" className="nav-link">
              Available Rooms
            </Link>
          </li>
          <li>
            <Link to="/my-bookings" className="nav-link">
              My Bookings
            </Link>
          </li>
          <li>
            <button onClick={handleSignOut} className="nav-btn">
              Sign Out
            </button>
          </li>
        </ul>
      </div>
    </nav>
  );
};

export default Navbar;
