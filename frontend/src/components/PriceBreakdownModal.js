import React from 'react';
import './PriceBreakdownModal.css';

const PriceBreakdownModal = ({ breakdown, onConfirm, onCancel, loading }) => {
  if (!breakdown) return null;

  return (
    <div className="modal-overlay">
      <div className="modal-content">
        <h3>Booking Price Breakdown</h3>
        
        <div className="breakdown-details">
          <div className="detail-row">
            <span>Room:</span>
            <span className="detail-value">{breakdown.room_name}</span>
          </div>
          <div className="detail-row">
            <span>Location:</span>
            <span className="detail-value">{breakdown.location}</span>
          </div>
          <div className="detail-row">
            <span>Date:</span>
            <span className="detail-value">{breakdown.date}</span>
          </div>
          
          <hr />
          
          <div className="detail-row">
            <span>Base Price:</span>
            <span className="detail-value">${breakdown.base_price}</span>
          </div>
          
          <div className="detail-row weather-info">
            <span>Temperature:</span>
            <span className="detail-value">{breakdown.temperature}°C</span>
          </div>
          
          <div className="detail-row weather-info">
            <span>Difference from 21°C:</span>
            <span className="detail-value">{breakdown.temperature_difference}°C</span>
          </div>
          
          {breakdown.weather_charge_percentage > 0 && (
            <div className="detail-row highlight">
              <span>Air Condition Charge ({breakdown.weather_charge_percentage}%):</span>
              <span className="detail-value">+${breakdown.weather_charge}</span>
            </div>
          )}
          
          {breakdown.weather_charge_percentage === 0 && (
            <div className="detail-row success">
              <span>Air Condition Charge:</span>
              <span className="detail-value">No additional charge</span>
            </div>
          )}
          
          <hr />
          
          <div className="detail-row total">
            <span>Total Price:</span>
            <span className="detail-value">${breakdown.total_price}</span>
          </div>
        </div>
        
        <div className="modal-actions">
          <button onClick={onCancel} className="cancel-button" disabled={loading}>
            Cancel
          </button>
          <button onClick={onConfirm} className="confirm-button" disabled={loading}>
            {loading ? 'Booking...' : 'Confirm Booking'}
          </button>
        </div>
      </div>
    </div>
  );
};

export default PriceBreakdownModal;
