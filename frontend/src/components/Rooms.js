import React, { useState, useEffect } from 'react';
import { AgGridReact } from 'ag-grid-react';
import { ModuleRegistry, AllCommunityModule } from 'ag-grid-community';
import 'ag-grid-community/styles/ag-grid.css';
import 'ag-grid-community/styles/ag-theme-alpine.css';
import { getAvailableRooms, createBooking, getPriceBreakdown } from '../services/api';
import PriceBreakdownModal from './PriceBreakdownModal';
import './Rooms.css';

ModuleRegistry.registerModules([AllCommunityModule]);

const Rooms = () => {
  const [location, setLocation] = useState('New York');
  const [date, setDate] = useState(new Date().toISOString().split('T')[0]);
  const [rooms, setRooms] = useState([]);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [breakdown, setBreakdown] = useState(null);
  const [selectedRoom, setSelectedRoom] = useState(null);

  const locations = [
    'New York',
    'London',
    'Tokyo',
    'Sydney',
    'Paris',
    'Berlin',
    'Singapore',
    'Toronto',
  ];

  const columnDefs = [
    { field: 'roomName', headerName: 'Room Name', flex: 2 },
    { field: 'location', headerName: 'Location', flex: 1 },
    {
      field: 'price',
      headerName: 'Listed Price ($)',
      flex: 1,
      valueFormatter: (params) => `$${params.value}`,
    },
    {
      headerName: 'Actions',
      flex: 1,
      cellRenderer: (params) => {
        return (
          <button
            className="book-btn"
            onClick={() => handleBook(params.data)}
          >
            Book Now
          </button>
        );
      },
    },
  ];

  const fetchRooms = async () => {
    setLoading(true);
    setError('');
    setMessage('');
    try {
      const data = await getAvailableRooms(location, date);
      setRooms(data.available_rooms || []);
      setMessage(
        `Found ${data.available_count} available rooms out of ${data.total_rooms}`
      );
    } catch (err) {
      setError(err.response?.data?.error || 'Failed to fetch rooms');
    } finally {
      setLoading(false);
    }
  };

  const handleBook = async (room) => {
    try {
      setLoading(true);
      const data = await getPriceBreakdown(location, date, room.roomName);
      setBreakdown(data);
      setSelectedRoom(room);
      setShowModal(true);
    } catch (err) {
      alert(err.response?.data?.error || 'Failed to get price breakdown');
    } finally {
      setLoading(false);
    }
  };

  const handleConfirmBooking = async () => {
    try {
      await createBooking(location, date, selectedRoom.roomName);
      setShowModal(false);
      alert('Booking created successfully!');
      fetchRooms(); // Refresh the list
    } catch (err) {
      alert(err.response?.data?.error || 'Failed to create booking');
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setBreakdown(null);
    setSelectedRoom(null);
  };

  useEffect(() => {
    fetchRooms();
    // eslint-disable-next-line
  }, []);

  return (
    <div className="rooms-container">
      <h2>Available Conference Rooms</h2>

      <div className="filters">
        <div className="filter-group">
          <label>Location:</label>
          <select value={location} onChange={(e) => setLocation(e.target.value)}>
            {locations.map((loc) => (
              <option key={loc} value={loc}>
                {loc}
              </option>
            ))}
          </select>
        </div>

        <div className="filter-group">
          <label>Date:</label>
          <input
            type="date"
            value={date}
            onChange={(e) => setDate(e.target.value)}
          />
        </div>

        <button onClick={fetchRooms} disabled={loading} className="search-btn">
          {loading ? 'Loading...' : 'Search'}
        </button>
      </div>

      {message && <div className="info-message">{message}</div>}
      {error && <div className="error-message">{error}</div>}

      <div className="ag-theme-alpine" style={{ height: 400, width: '100%' }}>
        <AgGridReact
          rowData={rooms}
          columnDefs={columnDefs}
          domLayout="autoHeight"
          animateRows={true}
        />
      </div>

      {showModal && breakdown && (
        <PriceBreakdownModal
          breakdown={breakdown}
          onConfirm={handleConfirmBooking}
          onCancel={handleCloseModal}
        />
      )}
    </div>
  );
};

export default Rooms;
