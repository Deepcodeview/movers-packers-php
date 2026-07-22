import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, ScrollView, RefreshControl, ActivityIndicator, Dimensions, TouchableOpacity, SafeAreaView } from 'react-native';
import { getDashboard, logout } from '../utils/api';
import { LineChart } from 'react-native-chart-kit';

export default function DashboardScreen({ user, onLogout, navigation }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [data, setData] = useState(null);

  const fetchDashboardData = async () => {
    try {
      const response = await getDashboard();
      if (response.success) {
        setData(response);
      }
    } catch (error) {
      console.error('Error loading dashboard metrics:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const handleRefresh = () => {
    setRefreshing(true);
    fetchDashboardData();
  };

  const handleLogout = async () => {
    await logout();
    onLogout();
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#FF5E3A" />
      </View>
    );
  }

  const metrics = data?.metrics || {};
  const chartDataRaw = data?.chart_data || [];

  // Prep chart data
  const chartLabels = chartDataRaw.map(d => d.label.split(' ')[0]); // e.g. "Jan"
  const chartSales = chartDataRaw.map(d => d.sales);
  const chartCollections = chartDataRaw.map(d => d.collection);

  const screenWidth = Dimensions.get('window').width;

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <View>
          <Text style={styles.welcomeText}>Hello, {user.name} 👋</Text>
          <Text style={styles.companyText}>OM GUPTESWAR CRM</Text>
        </View>
        <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
          <Text style={styles.logoutText}>Logout</Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContainer}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={['#FF5E3A']} />}
      >
        {/* KPI Cards Row */}
        <View style={styles.kpiGrid}>
          <View style={[styles.kpiCard, { borderLeftColor: '#009688' }]}>
            <Text style={styles.kpiLabel}>Today's Sales</Text>
            <Text style={styles.kpiValue}>₹{(metrics.today_sales || 0).toLocaleString('en-IN')}</Text>
          </View>

          <View style={[styles.kpiCard, { borderLeftColor: '#10B981' }]}>
            <Text style={styles.kpiLabel}>Today's Cash</Text>
            <Text style={styles.kpiValue}>₹{(metrics.today_collection || 0).toLocaleString('en-IN')}</Text>
          </View>

          <View style={[styles.kpiCard, { borderLeftColor: '#EF4444' }]}>
            <Text style={styles.kpiLabel}>Outstanding Due</Text>
            <Text style={[styles.kpiValue, { color: '#EF4444' }]}>₹{(metrics.pending_payments || 0).toLocaleString('en-IN')}</Text>
          </View>

          <View style={[styles.kpiCard, { borderLeftColor: '#6366F1' }]}>
            <Text style={styles.kpiLabel}>Total Clients</Text>
            <Text style={styles.kpiValue}>{metrics.total_customers || 0}</Text>
          </View>
        </View>

        {/* Quick Menu / Access Directory */}
        <Text style={styles.menuTitle}>Quick Actions & Modules</Text>
        <View style={styles.menuGrid}>
          <TouchableOpacity style={styles.menuItemCardSmall} onPress={() => navigation.navigate('Clients')}>
            <Text style={styles.menuIcon}>👥</Text>
            <Text style={styles.menuItemText}>Clients</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.menuItemCardSmall} onPress={() => navigation.navigate('Quotations')}>
            <Text style={styles.menuIcon}>📄</Text>
            <Text style={styles.menuItemText}>Quotations</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.menuItemCardSmall} onPress={() => navigation.navigate('Invoices')}>
            <Text style={styles.menuIcon}>🧾</Text>
            <Text style={styles.menuItemText}>Invoices</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.menuItemCardLarge} onPress={() => navigation.navigate('Operations', { activeSegment: 'bilty' })}>
            <Text style={styles.menuIcon}>🚛</Text>
            <Text style={styles.menuItemText}>Lorry Receipts (Bilty)</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.menuItemCardLarge} onPress={() => navigation.navigate('Operations', { activeSegment: 'payments' })}>
            <Text style={styles.menuIcon}>💸</Text>
            <Text style={styles.menuItemText}>Collections (Receipts)</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.menuItemCardLarge} onPress={() => navigation.navigate('Operations', { activeSegment: 'audits' })}>
            <Text style={styles.menuIcon}>📋</Text>
            <Text style={styles.menuItemText}>GST & System Audits</Text>
          </TouchableOpacity>
        </View>

        {/* Shifting Trend Analytics Chart */}
        {chartLabels.length > 0 && (
          <View style={styles.chartCard}>
            <Text style={styles.chartTitle}>Monthly Business Trend (Last 6 Months)</Text>
            <LineChart
              data={{
                labels: chartLabels,
                datasets: [
                  {
                    data: chartSales.length ? chartSales : [0],
                    color: (opacity = 1) => `rgba(255, 94, 58, ${opacity})`, // Shifting sales
                    strokeWidth: 3,
                  },
                  {
                    data: chartCollections.length ? chartCollections : [0],
                    color: (opacity = 1) => `rgba(16, 185, 129, ${opacity})`, // collections
                    strokeWidth: 3,
                  }
                ],
                legend: ['Revenue', 'Cash Collected']
              }}
              width={screenWidth - 40}
              height={220}
              chartConfig={{
                backgroundColor: '#ffffff',
                backgroundGradientFrom: '#ffffff',
                backgroundGradientTo: '#ffffff',
                decimalPlaces: 0,
                color: (opacity = 1) => `rgba(100, 116, 139, ${opacity})`,
                labelColor: (opacity = 1) => `rgba(100, 116, 139, ${opacity})`,
                style: { borderRadius: 12 },
                propsForDots: { r: '4', strokeWidth: '1', stroke: '#ffffff' }
              }}
              bezier
              style={styles.chart}
            />
          </View>
        )}

        {/* Invoice Status Distribution Cards */}
        <View style={styles.statusCard}>
          <Text style={styles.cardTitle}>GST Invoices Tracking</Text>
          <View style={styles.statusRow}>
            <View style={styles.statusItem}>
              <View style={[styles.statusDot, { backgroundColor: '#10B981' }]} />
              <Text style={styles.statusLabel}>Paid ({metrics.invoice_status?.paid || 0})</Text>
            </View>
            <View style={styles.statusItem}>
              <View style={[styles.statusDot, { backgroundColor: '#F59E0B' }]} />
              <Text style={styles.statusLabel}>Partial ({metrics.invoice_status?.partial || 0})</Text>
            </View>
            <View style={styles.statusItem}>
              <View style={[styles.statusDot, { backgroundColor: '#EF4444' }]} />
              <Text style={styles.statusLabel}>Unpaid ({metrics.invoice_status?.unpaid || 0})</Text>
            </View>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  header: {
    paddingHorizontal: 20,
    paddingTop: 15,
    paddingBottom: 15,
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  welcomeText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#0F172A',
  },
  companyText: {
    fontSize: 11,
    fontWeight: '600',
    color: '#FF5E3A',
    letterSpacing: 0.5,
  },
  logoutBtn: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#EF4444',
  },
  logoutText: {
    fontSize: 12,
    color: '#EF4444',
    fontWeight: '600',
  },
  scrollContainer: {
    padding: 20,
    paddingBottom: 40,
  },
  kpiGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginBottom: 20,
  },
  kpiCard: {
    width: '48%',
    backgroundColor: '#ffffff',
    borderRadius: 8,
    padding: 12,
    marginBottom: 12,
    borderLeftWidth: 4,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 1,
  },
  kpiLabel: {
    fontSize: 11,
    color: '#64748B',
    fontWeight: '600',
    marginBottom: 4,
  },
  kpiValue: {
    fontSize: 15,
    fontWeight: '700',
    color: '#0F172A',
  },
  chartCard: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 1,
  },
  chartTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: '#1E293B',
    marginBottom: 12,
  },
  chart: {
    marginVertical: 8,
    borderRadius: 12,
  },
  statusCard: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 1,
  },
  cardTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: '#1E293B',
    marginBottom: 12,
  },
  statusRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  statusItem: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  statusDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    marginRight: 6,
  },
  statusLabel: {
    fontSize: 12,
    color: '#475569',
    fontWeight: '500',
  },
  menuTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: '#1E293B',
    marginTop: 10,
    marginBottom: 12,
  },
  menuGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginBottom: 20,
  },
  menuItemCardSmall: {
    width: '31%',
    backgroundColor: '#ffffff',
    borderRadius: 8,
    paddingVertical: 12,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 1,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 10,
  },
  menuItemCardLarge: {
    width: '48%',
    backgroundColor: '#ffffff',
    borderRadius: 8,
    paddingVertical: 12,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 1,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 10,
  },
  menuIcon: {
    fontSize: 20,
    marginBottom: 4,
  },
  menuItemText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#475569',
    textAlign: 'center',
  },
});
