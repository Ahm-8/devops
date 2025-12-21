import React, { useState, useEffect } from 'react';
import { AgGridReact } from 'ag-grid-react';
import { ModuleRegistry, AllCommunityModule } from 'ag-grid-community';
import 'ag-grid-community/styles/ag-grid.css';
import 'ag-grid-community/styles/ag-theme-alpine.css';
import { getBookings, deleteBooking } from '../services/api';
import './MyBookings.css';

ModuleRegistry.registerModules([AllCommunityModule]);

const MyBookings = () => {
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const columnDefs = [
    { field: 'location', headerName: 'Location', flex: 1 },
    { field: 'roomName', headerName: 'Room Name', flex: 2 },
    {
      field: 'date',
      headerName: 'Date',
      flex: 1,
      valueFormatter: (params) => {
        return new Date(params.value).toLocaleDateString();
      },
    },
    {
      field: 'price',
      headerName: 'Paid Price ($)',
      flex: 1,
      valueFormatter: (params) => `$${params.value}`,
    },
    {
      field: 'createdAt',
      headerName: 'Booked On',
      flex: 1,
      valueFormatter: (params) => {
        return new Date(params.value).toLocaleDateString();
      },
    },
    {
      headerName: 'Actions',
      flex: 1,
      cellRenderer: (params) => {
        return (
          <button
            className="cancel-btn"
            onClick={() => handleCancel(params.data)}
          >
            Cancel
          </button>
        );
      },
    },
  ];

  const fetchBookings = async () => {
    setLoading(true);
    setError('');
    try {
      const data = await getBookings();
      setBookings(data.bookings || []);
    } catch (err) {
      setError(err.response?.data?.error || 'Failed to fetch bookings');
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = async (booking) => {
    if (!window.confirm(`Cancel booking for ${booking.roomName} on ${booking.date}?`))
      return;

    try {
      await deleteBooking(booking.location, booking.date, booking.roomName);
      alert('Booking cancelled successfully!');
      fetchBookings(); // Refresh the list
    } catch (err) {
      alert(err.response?.data?.error || 'Failed to cancel booking');
    }
  };

  useEffect(() => {
    fetchBookings();
  }, []);

  return (
    <div className="bookings-container">
      <div className="header">
        <h2>My Bookings</h2>
        <button onClick={fetchBookings} disabled={loading} className="refresh-btn">
          {loading ? 'Loading...' : 'Refresh'}
        </button>
      </div>

      {error && <div className="error-message">{error}</div>}

      {!loading && bookings.length === 0 && (
        <div className="no-bookings">
          <p>No bookings found. Go to Available Rooms to make a booking!</p>
        </div>
      )}

      {bookings.length > 0 && (
        <div className="ag-theme-alpine" style={{ height: 400, width: '100%' }}>
          <AgGridReact
            rowData={bookings}
            columnDefs={columnDefs}
            domLayout="autoHeight"
            animateRows={true}
          />
        </div>
      )}
    </div>
  );
};

export default MyBookings;
