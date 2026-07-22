import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, FlatList, TextInput, TouchableOpacity, Modal, ActivityIndicator, Alert, SafeAreaView, ScrollView, KeyboardAvoidingView, Platform, Linking } from 'react-native';
import { getInvoices, getCustomers, getQuotations, addInvoice } from '../utils/api';
import { Picker } from '@react-native-picker/picker';

export default function InvoicesScreen() {
  const [invoices, setInvoices] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [quotations, setQuotations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [modalVisible, setModalVisible] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // Form Fields
  const [customerId, setCustomerId] = useState('');
  const [quotationId, setQuotationId] = useState('');
  const [fromCity, setFromCity] = useState('');
  const [toCity, setToCity] = useState('');
  const [invoiceDate, setInvoiceDate] = useState(new Date().toISOString().split('T')[0]);
  const [vehicleNumber, setVehicleNumber] = useState('');
  const [driverName, setDriverName] = useState('');

  // Costs
  const [freight, setFreight] = useState('0');
  const [packing, setPacking] = useState('0');
  const [loadingCharge, setLoadingCharge] = useState('0');
  const [unloading, setUnloading] = useState('0');
  const [unpacking, setUnpacking] = useState('0');
  const [escort, setEscort] = useState('0');

  const [gstType, setGstType] = useState('full_amount'); // full_amount
  const [gstRate, setGstRate] = useState('18');

  const fetchInitialData = async () => {
    try {
      const invRes = await getInvoices();
      const cRes = await getCustomers();
      const qRes = await getQuotations();
      if (invRes.success) setInvoices(invRes.invoices || []);
      if (cRes.success) setCustomers(cRes.customers || []);
      if (qRes.success) setQuotations(qRes.quotations || []);
    } catch (error) {
      console.error('Error fetching invoices details:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchInitialData();
  }, []);

  const handleQuotationChange = (qId) => {
    setQuotationId(qId);
    if (!qId) return;

    const q = quotations.find(item => item.id === qId);
    if (q) {
      setCustomerId(q.customer_id);
      setFromCity(q.from_city);
      setToCity(q.to_city);
      setPacking(q.packing_charge.toString());
      setUnpacking(q.unpacking_charge.toString());
      setLoadingCharge(q.loading_charge.toString());
      setUnloading(q.unloading_charge.toString());
      setEscort(q.escort_charge.toString());
      setGstRate(q.gst_rate.toString());
    }
  };

  const getSubtotal = () => {
    return (
      (parseFloat(freight) || 0) +
      (parseFloat(packing) || 0) +
      (parseFloat(loadingCharge) || 0) +
      (parseFloat(unloading) || 0) +
      (parseFloat(unpacking) || 0) +
      (parseFloat(escort) || 0)
    );
  };

  const getGstBase = () => {
    return gstType === 'freight_only' ? (parseFloat(freight) || 0) : getSubtotal();
  };

  const getGstAmount = () => {
    return (getGstBase() * (parseFloat(gstRate) || 0)) / 100;
  };

  const getGrandTotal = () => {
    return getSubtotal() + getGstAmount();
  };

  const handleSave = async () => {
    if (!customerId) {
      Alert.alert('Validation Error', 'Please select a customer.');
      return;
    }
    if (!fromCity.trim() || !toCity.trim()) {
      Alert.alert('Validation Error', 'Routing cities are required.');
      return;
    }

    setSubmitting(true);
    try {
      const response = await addInvoice({
        customer_id: customerId,
        quotation_id: quotationId || null,
        from_city: fromCity.trim(),
        to_city: toCity.trim(),
        invoice_date: invoiceDate,
        vehicle_number: vehicleNumber.trim(),
        driver_name: driverName.trim(),
        freight_charge: parseFloat(freight) || 0,
        packing_charge: parseFloat(packing) || 0,
        loading_charge: parseFloat(loadingCharge) || 0,
        unloading_charge: parseFloat(unloading) || 0,
        unpacking_charge: parseFloat(unpacking) || 0,
        escort_charge: parseFloat(escort) || 0,
        gst_type: gstType,
        gst_rate: parseFloat(gstRate) || 0,
      });

      if (response.success) {
        Alert.alert('Success', `Tax Invoice ${response.invoice_number} generated successfully!`);
        setModalVisible(false);
        // Clear fields
        setCustomerId('');
        setQuotationId('');
        setFromCity('');
        setToCity('');
        setVehicleNumber('');
        setDriverName('');
        setFreight('0');
        setPacking('0');
        setLoadingCharge('0');
        setUnloading('0');
        setUnpacking('0');
        setEscort('0');
        // Reload list
        setLoading(true);
        fetchInitialData();
      }
    } catch (error) {
      Alert.alert('Error', error.message || 'Failed to save invoice.');
    } finally {
      setSubmitting(false);
    }
  };

  const getCustomerName = (cId) => {
    const c = customers.find(item => item.id === cId);
    return c ? c.name : 'Unknown Customer';
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Header Bar */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>GST Tax Invoices</Text>
        <TouchableOpacity style={styles.addBtn} onPress={() => setModalVisible(true)}>
          <Text style={styles.addBtnText}>+ Create Invoice</Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#FF5E3A" />
        </View>
      ) : (
        <FlatList
          data={invoices}
          keyExtractor={item => item.id}
          contentContainerStyle={styles.listContainer}
          renderItem={({ item }) => (
            <View style={styles.invoiceCard}>
              <View style={styles.invHeader}>
                <Text style={styles.invNumber}>#{item.invoice_number}</Text>
                <Text style={styles.invDate}>{item.invoice_date}</Text>
              </View>
              <Text style={styles.invCustomer}>{getCustomerName(item.customer_id)}</Text>
              <Text style={styles.invRoute}>🛣️ {item.from_city} ➜ {item.to_city}</Text>
              
              <View style={styles.statusRow}>
                <View style={[styles.statusBadge, {
                  backgroundColor: item.status === 'Paid' ? '#ECFDF5' : item.status === 'Partially Paid' ? '#FFFBEB' : '#FEF2F2'
                }]}>
                  <Text style={[styles.statusText, {
                    color: item.status === 'Paid' ? '#059669' : item.status === 'Partially Paid' ? '#D97706' : '#DC2626'
                  }]}>{item.status}</Text>
                </View>
                <Text style={styles.invTotal}>₹{parseFloat(item.grand_total).toLocaleString('en-IN')}</Text>
              </View>
              <TouchableOpacity 
                style={styles.printShareBtn} 
                onPress={() => Linking.openURL(`https://manage.deepsde.in/invoices.php?action=view&id=${item.id}`)}
              >
                <Text style={styles.printShareBtnText}>🖨️ View / Print GST Invoice</Text>
              </TouchableOpacity>
            </View>
          )}
          ListEmptyComponent={
            <Text style={styles.emptyText}>No tax invoices billed yet.</Text>
          }
        />
      )}

      <Modal visible={modalVisible} animationType="slide" transparent>
        <KeyboardAvoidingView 
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'} 
          style={{ flex: 1 }}
        >
          <View style={styles.modalBg}>
            <View style={styles.modalContent}>
              <Text style={styles.modalTitle}>New GST Shifting Invoice</Text>
              <ScrollView style={styles.formScroll} contentContainerStyle={{ paddingBottom: 20 }}>
                
                {/* Quotation Selector link */}
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>Link Approved Quotation (Optional)</Text>
                  <View style={styles.pickerBorder}>
                    <Picker
                      selectedValue={quotationId}
                      onValueChange={handleQuotationChange}
                      style={styles.picker}
                    >
                      <Picker.Item label="-- Choose Quotation --" value="" />
                      {quotations.map(q => (
                        <Picker.Item key={q.id} label={`${q.quotation_number} - ${getCustomerName(q.customer_id)}`} value={q.id} />
                      ))}
                    </Picker>
                  </View>
                </View>

                {/* Customer Selector */}
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>Select Customer *</Text>
                  <View style={styles.pickerBorder}>
                    <Picker
                      selectedValue={customerId}
                      onValueChange={setCustomerId}
                      style={styles.picker}
                    >
                      <Picker.Item label="-- Select Customer --" value="" />
                      {customers.map(c => (
                        <Picker.Item key={c.id} label={c.name} value={c.id} />
                      ))}
                    </Picker>
                  </View>
                </View>

                {/* Routing */}
                <View style={styles.row}>
                  <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                    <Text style={styles.label}>From City *</Text>
                    <TextInput style={styles.input} placeholder="Origin" value={fromCity} onChangeText={setFromCity} />
                  </View>
                  <View style={[styles.inputGroup, { flex: 1 }]}>
                    <Text style={styles.label}>To City *</Text>
                    <TextInput style={styles.input} placeholder="Destination" value={toCity} onChangeText={setToCity} />
                  </View>
                </View>

                {/* Shifting vehicle details */}
                <View style={styles.row}>
                  <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                    <Text style={styles.label}>Truck / Vehicle No.</Text>
                    <TextInput style={styles.input} placeholder="e.g. OD-02-B-5555" autoCapitalize="characters" value={vehicleNumber} onChangeText={setVehicleNumber} />
                  </View>
                  <View style={[styles.inputGroup, { flex: 1 }]}>
                    <Text style={styles.label}>Driver Name</Text>
                    <TextInput style={styles.input} placeholder="e.g. Suresh Kumar" value={driverName} onChangeText={setDriverName} />
                  </View>
                </View>

                {/* Invoice Breakdown */}
                <Text style={styles.sectionHeading}>Tax Invoice Service Particulars (₹)</Text>
                
                <View style={styles.row}>
                  <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                    <Text style={styles.label}>Freight / Escort Charges</Text>
                    <TextInput style={styles.input} keyboardType="numeric" value={freight} onChangeText={setFreight} />
                  </View>
                  <View style={[styles.inputGroup, { flex: 1 }]}>
                    <Text style={styles.label}>Packing Charges</Text>
                    <TextInput style={styles.input} keyboardType="numeric" value={packing} onChangeText={setPacking} />
                  </View>
                </View>

                <View style={styles.row}>
                  <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                    <Text style={styles.label}>Loading Charges</Text>
                    <TextInput style={styles.input} keyboardType="numeric" value={loadingCharge} onChangeText={setLoadingCharge} />
                  </View>
                  <View style={[styles.inputGroup, { flex: 1 }]}>
                    <Text style={styles.label}>Unloading Charges</Text>
                    <TextInput style={styles.input} keyboardType="numeric" value={unloading} onChangeText={setUnloading} />
                  </View>
                </View>

                <View style={styles.row}>
                  <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                    <Text style={styles.label}>Unpacking Charges</Text>
                    <TextInput style={styles.input} keyboardType="numeric" value={unpacking} onChangeText={setUnpacking} />
                  </View>
                  <View style={[styles.inputGroup, { flex: 1 }]}>
                    <Text style={styles.label}>Other Transit Charges</Text>
                    <TextInput style={styles.input} keyboardType="numeric" value={escort} onChangeText={setEscort} />
                  </View>
                </View>

                <View style={styles.row}>
                  <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                    <Text style={styles.label}>GST Tax Rate (%)</Text>
                    <TextInput style={styles.input} keyboardType="numeric" value={gstRate} onChangeText={setGstRate} />
                  </View>
                  <View style={[styles.inputGroup, { flex: 1 }]}>
                    <Text style={styles.label}>Calculate GST On</Text>
                    <View style={styles.pickerBorder}>
                      <Picker
                        selectedValue={gstType}
                        onValueChange={setGstType}
                        style={styles.picker}
                      >
                        <Picker.Item label="Full Amount Subtotal" value="full_amount" />
                        <Picker.Item label="Freight Only" value="freight_only" />
                      </Picker>
                    </View>
                  </View>
                </View>

                {/* Real-time Summary Box */}
                <View style={styles.summaryBox}>
                  <View style={styles.summaryRow}>
                    <Text style={styles.summaryLabel}>Subtotal Shifting Cost:</Text>
                    <Text style={styles.summaryValue}>₹{getSubtotal().toLocaleString('en-IN')}</Text>
                  </View>
                  <View style={styles.summaryRow}>
                    <Text style={styles.summaryLabel}>GST ({gstRate}%):</Text>
                    <Text style={styles.summaryValue}>₹{getGstAmount().toLocaleString('en-IN')}</Text>
                  </View>
                  <View style={[styles.summaryRow, styles.totalBorder]}>
                    <Text style={styles.totalLabel}>Billing Grand Total:</Text>
                    <Text style={styles.totalValue}>₹{getGrandTotal().toLocaleString('en-IN')}</Text>
                  </View>
                </View>

                <View style={[styles.modalActions, { marginTop: 20 }]}>
                  <TouchableOpacity style={styles.cancelBtn} onPress={() => setModalVisible(false)} disabled={submitting}>
                    <Text style={styles.cancelBtnText}>Cancel</Text>
                  </TouchableOpacity>
                  <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={submitting}>
                    {submitting ? <ActivityIndicator color="#ffffff" /> : <Text style={styles.saveBtnText}>Save Invoice</Text>}
                  </TouchableOpacity>
                </View>

              </ScrollView>
            </View>
          </View>
        </KeyboardAvoidingView>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
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
  headerTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#0F172A',
  },
  addBtn: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 6,
    backgroundColor: '#FF5E3A',
  },
  addBtnText: {
    fontSize: 12,
    color: '#ffffff',
    fontWeight: '700',
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  listContainer: {
    padding: 16,
  },
  invoiceCard: {
    backgroundColor: '#ffffff',
    borderRadius: 10,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.03,
    shadowRadius: 4,
    elevation: 1,
  },
  invHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  invNumber: {
    fontSize: 13,
    fontWeight: '800',
    color: '#FF5E3A',
  },
  invDate: {
    fontSize: 11,
    color: '#94A3B8',
  },
  invCustomer: {
    fontSize: 14,
    fontWeight: '700',
    color: '#1E293B',
    marginBottom: 4,
  },
  invRoute: {
    fontSize: 12,
    color: '#475569',
    marginBottom: 12,
  },
  statusRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
    paddingTop: 10,
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  statusText: {
    fontSize: 11,
    fontWeight: '700',
  },
  invTotal: {
    fontSize: 14,
    fontWeight: '800',
    color: '#0F172A',
  },
  emptyText: {
    textAlign: 'center',
    color: '#94A3B8',
    fontSize: 13,
    marginTop: 30,
  },
  modalBg: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.4)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#ffffff',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    padding: 20,
    paddingBottom: Platform.OS === 'ios' ? 40 : 30,
    maxHeight: '90%',
  },
  modalTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#0F172A',
    marginBottom: 16,
    textAlign: 'center',
  },
  formScroll: {
    marginBottom: 20,
  },
  sectionHeading: {
    fontSize: 13,
    fontWeight: '700',
    color: '#FF5E3A',
    marginTop: 18,
    marginBottom: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#FFF1EE',
    paddingBottom: 4,
  },
  inputGroup: {
    marginBottom: 14,
  },
  row: {
    flexDirection: 'row',
  },
  label: {
    fontSize: 11,
    fontWeight: '600',
    color: '#475569',
    marginBottom: 6,
  },
  input: {
    height: 40,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 6,
    paddingHorizontal: 10,
    fontSize: 13,
    color: '#0F172A',
  },
  pickerBorder: {
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 6,
    backgroundColor: '#F8FAFC',
    overflow: 'hidden',
  },
  picker: {
    height: 40,
  },
  summaryBox: {
    backgroundColor: '#FFF8F6',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#FFE6E1',
    padding: 12,
    marginTop: 16,
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 4,
  },
  summaryLabel: {
    fontSize: 12,
    color: '#64748B',
    fontWeight: '500',
  },
  summaryValue: {
    fontSize: 12,
    color: '#334155',
    fontWeight: '700',
  },
  totalBorder: {
    borderTopWidth: 1,
    borderTopColor: '#FFE6E1',
    marginTop: 6,
    paddingTop: 8,
  },
  totalLabel: {
    fontSize: 13,
    fontWeight: '700',
    color: '#FF5E3A',
  },
  totalValue: {
    fontSize: 14,
    fontWeight: '800',
    color: '#FF5E3A',
  },
  modalActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  cancelBtn: {
    flex: 1,
    height: 44,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 10,
  },
  cancelBtnText: {
    fontSize: 14,
    color: '#64748B',
    fontWeight: '600',
  },
  saveBtn: {
    flex: 2,
    height: 44,
    backgroundColor: '#FF5E3A',
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  saveBtnText: {
    fontSize: 14,
    color: '#ffffff',
    fontWeight: '700',
  },
  printShareBtn: {
    marginTop: 10,
    backgroundColor: '#FFF1EE',
    borderWidth: 1,
    borderColor: '#FF5E3A',
    borderRadius: 6,
    paddingVertical: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  printShareBtnText: {
    fontSize: 12,
    color: '#FF5E3A',
    fontWeight: '700',
  },
});
