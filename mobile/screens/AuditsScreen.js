import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, FlatList, TextInput, TouchableOpacity, ActivityIndicator, Alert, ScrollView, Linking } from 'react-native';
import { getGstAudit, getAuditLogs } from '../utils/api';

export default function AuditsScreen() {
  const [subSegment, setSubSegment] = useState('gst'); // 'gst' or 'logs'
  const [loading, setLoading] = useState(false);

  // GST audit dates
  const today = new Date();
  const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
  const lastDay = today.toISOString().split('T')[0];

  const [startDate, setStartDate] = useState(firstDayOfMonth);
  const [endDate, setEndDate] = useState(lastDay);
  const [gstData, setGstData] = useState({ invoices: [], totals: {} });

  // Logs state
  const [logs, setLogs] = useState([]);

  useEffect(() => {
    if (subSegment === 'gst') {
      fetchGstAudit();
    } else {
      fetchLogs();
    }
  }, [subSegment]);

  const fetchGstAudit = async () => {
    setLoading(true);
    try {
      const res = await getGstAudit(startDate, endDate);
      if (res.success) {
        setGstData({
          invoices: res.invoices || [],
          totals: res.totals || {}
        });
      }
    } catch (e) {
      Alert.alert('Error', 'Failed to retrieve GST audit details');
    } finally {
      setLoading(false);
    }
  };

  const fetchLogs = async () => {
    setLoading(true);
    try {
      const res = await getAuditLogs();
      if (res.success) {
        setLogs(res.logs || []);
      }
    } catch (e) {
      Alert.alert('Error', 'Failed to retrieve audit log data');
    } finally {
      setLoading(false);
    }
  };

  const shareGstReport = () => {
    const shareText = `GST Return Report (${startDate} to ${endDate})\nGross Sales: ₹${parseFloat(gstData.totals.sales || 0).toLocaleString('en-IN')}\nTotal Tax: ₹${parseFloat(gstData.totals.gst || 0).toLocaleString('en-IN')}\nView Audit Center: https://manage.deepsde.in/gst_audit.php?start_date=${startDate}&end_date=${endDate}`;
    Linking.openURL(`whatsapp://send?text=${encodeURIComponent(shareText)}`).catch(() => {
      Linking.openURL(`https://wa.me/?text=${encodeURIComponent(shareText)}`);
    });
  };

  return (
    <View style={styles.container}>
      {/* Sub tabs picker */}
      <View style={styles.tabBar}>
        <TouchableOpacity 
          style={[styles.tabBtn, subSegment === 'gst' && styles.activeTabBtn]} 
          onPress={() => setSubSegment('gst')}
        >
          <Text style={[styles.tabBtnText, subSegment === 'gst' && styles.activeTabBtnText]}>📊 GST Returns Audit</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={[styles.tabBtn, subSegment === 'logs' && styles.activeTabBtn]} 
          onPress={() => setSubSegment('logs')}
        >
          <Text style={[styles.tabBtnText, subSegment === 'logs' && styles.activeTabBtnText]}>📋 System Logs</Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#FF5E3A" />
        </View>
      ) : subSegment === 'gst' ? (
        <ScrollView style={styles.scrollContainer} contentContainerStyle={{ padding: 16 }}>
          {/* Date controls */}
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Audit Period Range</Text>
            <View style={styles.row}>
              <View style={[styles.inputGroup, { marginRight: 8 }]}>
                <Text style={styles.label}>Start Date</Text>
                <TextInput style={styles.input} placeholder="YYYY-MM-DD" value={startDate} onChangeText={setStartDate} />
              </View>
              <View style={styles.inputGroup}>
                <Text style={styles.label}>End Date</Text>
                <TextInput style={styles.input} placeholder="YYYY-MM-DD" value={endDate} onChangeText={setEndDate} />
              </View>
            </View>
            <TouchableOpacity style={styles.genBtn} onPress={fetchGstAudit}>
              <Text style={styles.genBtnText}>Generate Audit Data</Text>
            </TouchableOpacity>
          </View>

          {/* Tax Audit summary */}
          <View style={styles.summaryGrid}>
            <View style={styles.gridCard}>
              <Text style={styles.gridLabel}>Gross Sales</Text>
              <Text style={styles.gridValue}>₹{parseFloat(gstData.totals.sales || 0).toLocaleString('en-IN')}</Text>
            </View>
            <View style={styles.gridCard}>
              <Text style={styles.gridLabel}>Total GST Tax</Text>
              <Text style={styles.gridValue}>₹{parseFloat(gstData.totals.gst || 0).toLocaleString('en-IN')}</Text>
            </View>
            <View style={styles.gridCard}>
              <Text style={styles.gridLabel}>Taxable Sales</Text>
              <Text style={styles.gridValue}>₹{parseFloat(gstData.totals.taxable || 0).toLocaleString('en-IN')}</Text>
            </View>
            <View style={styles.gridCard}>
              <Text style={styles.gridLabel}>CGST Tax</Text>
              <Text style={styles.gridValue}>₹{parseFloat(gstData.totals.cgst || 0).toLocaleString('en-IN')}</Text>
            </View>
            <View style={styles.gridCard}>
              <Text style={styles.gridLabel}>SGST Tax</Text>
              <Text style={styles.gridValue}>₹{parseFloat(gstData.totals.sgst || 0).toLocaleString('en-IN')}</Text>
            </View>
            <View style={styles.gridCard}>
              <Text style={styles.gridLabel}>IGST Tax</Text>
              <Text style={styles.gridValue}>₹{parseFloat(gstData.totals.igst || 0).toLocaleString('en-IN')}</Text>
            </View>
          </View>

          {/* PDF and Excel buttons */}
          <View style={styles.actionsRow}>
            <TouchableOpacity 
              style={[styles.actionBtn, { backgroundColor: '#FFF1EE', borderColor: '#FF5E3A', borderWidth: 1 }]} 
              onPress={() => Linking.openURL(`https://manage.deepsde.in/gst_audit.php?start_date=${startDate}&end_date=${endDate}`)}
            >
              <Text style={[styles.actionBtnText, { color: '#FF5E3A' }]}>🖨️ Print Audit PDF</Text>
            </TouchableOpacity>
            <TouchableOpacity 
              style={[styles.actionBtn, { backgroundColor: '#FF5E3A' }]}
              onPress={() => Linking.openURL(`https://manage.deepsde.in/gst_audit.php?start_date=${startDate}&end_date=${endDate}&export=excel`)}
            >
              <Text style={[styles.actionBtnText, { color: '#ffffff' }]}>📥 Export Excel</Text>
            </TouchableOpacity>
          </View>

          <TouchableOpacity style={styles.whatsappBtn} onPress={shareGstReport}>
            <Text style={styles.whatsappBtnText}>💬 Share Audit Summary via WhatsApp</Text>
          </TouchableOpacity>

          {/* Ledger title */}
          <Text style={styles.sectionHeading}>GST Returns Invoices Ledger</Text>
          {gstData.invoices.length === 0 ? (
            <Text style={styles.emptyText}>No filtered invoices in this date range.</Text>
          ) : (
            gstData.invoices.map((item, idx) => (
              <View key={idx} style={styles.ledgerCard}>
                <View style={styles.ledgerHeader}>
                  <Text style={styles.ledgerNo}>{item.invoice_no}</Text>
                  <Text style={styles.ledgerDate}>{item.invoice_date}</Text>
                </View>
                <Text style={styles.ledgerCust}>{item.customer_name}</Text>
                {item.customer_gstin ? <Text style={styles.ledgerGstin}>GSTIN: {item.customer_gstin}</Text> : null}
                <Text style={styles.ledgerRoute}>🛣️ Pos: {item.state}</Text>
                <View style={styles.ledgerTotals}>
                  <Text style={styles.ledgerText}>Taxable: ₹{parseFloat(item.taxable_amount || 0).toLocaleString('en-IN')}</Text>
                  <Text style={styles.ledgerText}>GST ({item.gst_rate}%): ₹{parseFloat(item.gst_amount || 0).toLocaleString('en-IN')}</Text>
                </View>
                <Text style={styles.ledgerGrand}>Grand Total: ₹{parseFloat(item.grand_total || 0).toLocaleString('en-IN')}</Text>
              </View>
            ))
          )}
        </ScrollView>
      ) : (
        /* Audit Logs List */
        <FlatList
          data={logs}
          keyExtractor={(item) => item.id}
          contentContainerStyle={{ padding: 16 }}
          renderItem={({ item }) => (
            <View style={styles.logCard}>
              <View style={styles.logHeader}>
                <View style={styles.badge}>
                  <Text style={styles.badgeText}>{item.action_type}</Text>
                </View>
                <Text style={styles.logTime}>{item.timestamp}</Text>
              </View>
              <Text style={styles.logDesc}>{item.description}</Text>
              <Text style={styles.logUser}>👤 User: {item.user_name || 'System'}</Text>
            </View>
          )}
          ListEmptyComponent={
            <Text style={styles.emptyText}>No audit activity logs recorded yet.</Text>
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  tabBar: {
    flexDirection: 'row',
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
    padding: 6,
  },
  tabBtn: {
    flex: 1,
    paddingVertical: 10,
    alignItems: 'center',
    borderRadius: 6,
  },
  activeTabBtn: {
    backgroundColor: '#F1F5F9',
  },
  tabBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#64748B',
  },
  activeTabBtnText: {
    color: '#0F172A',
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  scrollContainer: {
    flex: 1,
  },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 8,
    padding: 16,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 16,
  },
  cardTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: '#0F172A',
    marginBottom: 12,
  },
  row: {
    flexDirection: 'row',
  },
  inputGroup: {
    flex: 1,
  },
  label: {
    fontSize: 11,
    color: '#64748B',
    fontWeight: '600',
    marginBottom: 4,
  },
  input: {
    height: 40,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 6,
    paddingHorizontal: 10,
    fontSize: 13,
    backgroundColor: '#F8FAFC',
    color: '#0F172A',
  },
  genBtn: {
    backgroundColor: '#FF5E3A',
    borderRadius: 6,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 12,
  },
  genBtnText: {
    fontSize: 13,
    color: '#ffffff',
    fontWeight: '700',
  },
  summaryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  gridCard: {
    width: '48%',
    backgroundColor: '#ffffff',
    borderRadius: 8,
    padding: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 10,
  },
  gridLabel: {
    fontSize: 10,
    color: '#64748B',
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  gridValue: {
    fontSize: 14,
    fontWeight: '800',
    color: '#0F172A',
    marginTop: 4,
  },
  actionsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  actionBtn: {
    flex: 1,
    height: 40,
    borderRadius: 6,
    alignItems: 'center',
    justifyContent: 'center',
    marginHorizontal: 4,
  },
  actionBtnText: {
    fontSize: 12,
    fontWeight: '700',
  },
  whatsappBtn: {
    backgroundColor: '#25D366',
    borderRadius: 6,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 20,
  },
  whatsappBtnText: {
    fontSize: 12,
    color: '#ffffff',
    fontWeight: '700',
  },
  sectionHeading: {
    fontSize: 14,
    fontWeight: '800',
    color: '#0F172A',
    marginVertical: 12,
  },
  emptyText: {
    fontSize: 13,
    color: '#94A3B8',
    textAlign: 'center',
    marginVertical: 20,
  },
  ledgerCard: {
    backgroundColor: '#ffffff',
    borderRadius: 8,
    padding: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 10,
  },
  ledgerHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  ledgerNo: {
    fontSize: 12,
    fontWeight: '700',
    color: '#FF5E3A',
  },
  ledgerDate: {
    fontSize: 11,
    color: '#64748B',
  },
  ledgerCust: {
    fontSize: 13,
    fontWeight: '700',
    color: '#0F172A',
  },
  ledgerGstin: {
    fontSize: 11,
    color: '#FF5E3A',
    fontWeight: '600',
    marginTop: 2,
  },
  ledgerRoute: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 2,
  },
  ledgerTotals: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 8,
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
    paddingTop: 6,
  },
  ledgerText: {
    fontSize: 11,
    color: '#475569',
  },
  ledgerGrand: {
    fontSize: 12,
    fontWeight: '700',
    color: '#0F172A',
    textAlign: 'right',
    marginTop: 4,
  },
  logCard: {
    backgroundColor: '#ffffff',
    borderRadius: 8,
    padding: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 10,
  },
  logHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  badge: {
    backgroundColor: '#F1F5F9',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 4,
  },
  badgeText: {
    fontSize: 9,
    fontWeight: '700',
    color: '#475569',
    textTransform: 'uppercase',
  },
  logTime: {
    fontSize: 10,
    color: '#94A3B8',
  },
  logDesc: {
    fontSize: 13,
    color: '#334155',
    lineHeight: 18,
  },
  logUser: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 6,
    fontWeight: '600',
  },
});
