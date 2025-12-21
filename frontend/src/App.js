import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { isAuthenticated } from './services/auth';
import Auth from './components/Auth';
import Rooms from './components/Rooms';
import MyBookings from './components/MyBookings';
import Navbar from './components/Navbar';
import './App.css';

const PrivateRoute = ({ children }) => {
  return isAuthenticated() ? (
    <>
      <Navbar />
      {children}
    </>
  ) : (
    <Navigate to="/" />
  );
};

function App() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<Auth />} />
        <Route
          path="/rooms"
          element={
            <PrivateRoute>
              <Rooms />
            </PrivateRoute>
          }
        />
        <Route
          path="/my-bookings"
          element={
            <PrivateRoute>
              <MyBookings />
            </PrivateRoute>
          }
        />
      </Routes>
    </Router>
  );
}

export default App;
