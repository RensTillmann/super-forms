import React, { useState } from 'react';
import { BarChart, TrendingUp, Users, Clock, Calendar, Download, Filter } from 'lucide-react';
import { AnalyticsPanelProps, FormAnalytics } from '../types/panel.types';
import { BasePanel } from './BasePanel';

const defaultAnalytics: FormAnalytics = {
  totalViews: 1234,
  submissions: 456,
  conversionRate: 37,
  averageTime: '2.5min'
};

export const AnalyticsPanel: React.FC<AnalyticsPanelProps> = ({
  isOpen,
  onClose,
  analytics = defaultAnalytics,
  ...basePanelProps
}) => {
  const [dateRange, setDateRange] = useState('week');
  const [chartType, setChartType] = useState('line');

  return (
    <BasePanel
      isOpen={isOpen}
      onClose={onClose}
      title="Form Analytics"
      size="lg"
      {...basePanelProps}
    >
      <div className="analytics-content">
        {/* Header Controls */}
        <div className="analytics-header">
          <div className="analytics-date-range">
            <Calendar size={16} />
            <select 
              value={dateRange} 
              onChange={(e) => setDateRange(e.target.value)}
              className="form-select"
            >
              <option value="today">Today</option>
              <option value="week">Last 7 days</option>
              <option value="month">Last 30 days</option>
              <option value="year">Last year</option>
              <option value="all">All time</option>
            </select>
          </div>
          
          <div className="analytics-actions">
            <button className="btn btn-sm btn-outline">
              <Filter size={14} />
              Filter
            </button>
            <button className="btn btn-sm btn-outline">
              <Download size={14} />
              Export
            </button>
          </div>
        </div>

        {/* Stats Grid */}
        <div className="analytics-stats">
          <div className="stat-card">
            <div className="stat-icon stat-icon-blue">
              <Users size={20} />
            </div>
            <div className="stat-content">
              <div className="stat-number">{analytics.totalViews.toLocaleString()}</div>
              <div className="stat-label">Total Views</div>
              <div className="stat-change stat-change-positive">
                <TrendingUp size={12} />
                +12% from last period
              </div>
            </div>
          </div>
          
          <div className="stat-card">
            <div className="stat-icon stat-icon-green">
              <BarChart size={20} />
            </div>
            <div className="stat-content">
              <div className="stat-number">{analytics.submissions.toLocaleString()}</div>
              <div className="stat-label">Submissions</div>
              <div className="stat-change stat-change-positive">
                <TrendingUp size={12} />
                +8% from last period
              </div>
            </div>
          </div>
          
          <div className="stat-card">
            <div className="stat-icon stat-icon-purple">
              <TrendingUp size={20} />
            </div>
            <div className="stat-content">
              <div className="stat-number">{analytics.conversionRate}%</div>
              <div className="stat-label">Conversion Rate</div>
              <div className="stat-change stat-change-negative">
                <TrendingUp size={12} className="rotate-180" />
                -2% from last period
              </div>
            </div>
          </div>
          
          <div className="stat-card">
            <div className="stat-icon stat-icon-orange">
              <Clock size={20} />
            </div>
            <div className="stat-content">
              <div className="stat-number">{analytics.averageTime}</div>
              <div className="stat-label">Avg. Time</div>
              <div className="stat-change stat-change-neutral">
                No change
              </div>
            </div>
          </div>
        </div>

        {/* Chart Section */}
        <div className="analytics-chart-section">
          <div className="analytics-chart-header">
            <h4>Submissions Over Time</h4>
            <div className="chart-type-selector">
              <button 
                className={`chart-type-btn ${chartType === 'line' ? 'active' : ''}`}
                onClick={() => setChartType('line')}
              >
                Line
              </button>
              <button 
                className={`chart-type-btn ${chartType === 'bar' ? 'active' : ''}`}
                onClick={() => setChartType('bar')}
              >
                Bar
              </button>
            </div>
          </div>
          
          <div className="analytics-chart">
            <div className="chart-placeholder">
              <BarChart size={48} className="text-gray-400" />
              <p>Chart visualization would go here</p>
              <p className="text-sm text-gray-500">
                Integrate with your preferred charting library (Chart.js, Recharts, etc.)
              </p>
            </div>
          </div>
        </div>

        {/* Additional Insights */}
        <div className="analytics-insights">
          <h4 className="mb-3">Key Insights</h4>
          <div className="insight-list">
            <div className="insight-item">
              <div className="insight-icon">📈</div>
              <div className="insight-content">
                <p className="insight-title">Peak submission time</p>
                <p className="insight-value">Weekdays between 2-4 PM</p>
              </div>
            </div>
            <div className="insight-item">
              <div className="insight-icon">⏱️</div>
              <div className="insight-content">
                <p className="insight-title">Completion rate</p>
                <p className="insight-value">82% of users who start complete the form</p>
              </div>
            </div>
            <div className="insight-item">
              <div className="insight-icon">📱</div>
              <div className="insight-content">
                <p className="insight-title">Device breakdown</p>
                <p className="insight-value">65% Desktop, 30% Mobile, 5% Tablet</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </BasePanel>
  );
};