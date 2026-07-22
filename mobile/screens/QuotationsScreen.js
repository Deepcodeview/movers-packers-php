import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, FlatList, TextInput, TouchableOpacity, Modal, ActivityIndicator, Alert, SafeAreaView, ScrollView } from 'react-native';
import { getQuotations, getCustomers, addQuotation } from '../utils/api';
import { Picker } from '@react-native-picker/picker';

export default function QuotationsScreen() {
  const [quotations, setQuotations] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [modalVisible, setModalVisible] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // Form Fields
  const [customerId, setCustomerId] = useState('');
  const [fromCity, setFromCity] = useState('');
  const [toCity, setToCity] = useState('');
  const [phone, setPhone] = useState('');
  const [quotationDate, setQuotationDate] = useState(new Date().toISOString().split('T')[0]);

  // Inventory Checklist (Standard Moving Items Stepper)
  const [checklist, setChecklist] = useState({
    'Double Bed': 0,
    'Single Bed': 0,
    'Sofa 3 Seater': 0,
    'Sofa 1 Seater': 0,
    'Dining Table': 0,
    'LCD/LED TV': 0,
    'Refrigerator': 0,
    'Washing Machine': 0,
    'Almirah': 0,
    'Carton Boxes': 0,
    'Kitchen Utensils (Box)': 0,
    'Two Wheeler / Bike': 0
  });

  // Shifting Charges
  const [packing, setPacking] = useState('0');
  const [unpacking, setUnpacking] = useState('0');
  const [loadingCharge, setLoadingCharge] = useState('0');
  const [unloading, setUnloading] = useState('0');
  const [escort, setEscort] = useState('0');
  const [storage, setStorage] = useState('0');
  const [insurance, setInsurance] = useState('0');
  const [gstRate, setGstRate] = useState('18'); // Default 18% GST

  const fetchInitialData = async () => {
    try {
      const qRes = await getQuotations();
      const cRes = await getCustomers();
      if (qRes.success) setQuotations(qRes.quotations || []);
      if (cRes.success) setCustomers(cRes.customers || []);
    } catch (error) {
      console.error('Error fetching quotations details:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchInitialData();
  }, []);

  const handleQtyChange = (key, type) => {
    setChecklist(prev => {
      const current = prev[key];
      const next = type === 'plus' ? current + 1 : Math.max(0, current - 1);
      return { ...prev, [key]: next };
    });
  };

  // Live Shifting Estimation Calculator
  const getSubtotal = () => {
    return (
      (parseFloat(packing) || 0) +
      (parseFloat(unpacking) || 0) +
      (parseFloat(loadingCharge) || 0) +
      (parseFloat(unloading) || 0) +
      (parseFloat(escort) || 0) +
      (parseFloat(storage) || 0) +
      (parseFloat(insurance) || 0)
    );
  };

  const getGstAmount = () => {
    return (getSubtotal() * (parseFloat(gstRate) || 0)) / 100;
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
      Alert.alert('Validation Error', 'Please specify origin and destination cities.');
      return;
    }

    // Filter out items with 0 count
    const activeItems = {};
    Object.keys(checklist).forEach(key => {
      if (checklist[key] > 0) {
        activeItems[key] = checklist[key];
      }
    });

    setSubmitting(true);
    try {
      const response = await addQuotation({
        customer_id: customerId,
        from_city: fromCity.trim(),
        to_city: toCity.trim(),
        phone: phone.trim(),
        quotation_date: quotationDate,
        items: activeItems,
        packing_charge: parseFloat(packing) || 0,
        unpacking_charge: parseFloat(unpacking) || 0,
        loading_charge: parseFloat(loadingCharge) || 0,
        unloading_charge: parseFloat(unloading) || 0,
        escort_charge: parseFloat(escort) || 0,
        storage_charge: parseFloat(storage) || 0,
        insurance_charge: parseFloat(insurance) || 0,
        gst_rate: parseFloat(gstRate) || 0,
      });

      if (response.success) {
        Alert.alert('Success', `Quotation ${response.quotation_number} generated successfully!`);
        setModalVisible(false);
        // Reset inputs
        setCustomerId('');
        setFromCity('');
        setToCity('');
        setPhone('');
        setPacking('0');
        setUnpacking('0');
        setLoadingCharge('0');
        setUnloading('0');
        setEscort('0');
        setStorage('0');
        setInsurance('0');
        setChecklist({
          'Double Bed': 0, 'Single Bed': 0, 'Sofa 3 Seater': 0, 'Sofa 1 Seater': 0,
          'Dining Table': 0, 'LCD/LED TV': 0, 'Refrigerator': 0, 'Washing Machine': 0,
          'Almirah': 0, 'Carton Boxes': 0, 'Kitchen Utensils (Box)': 0, 'Two Wheeler / Bike': 0
        });
        // Reload list
        setLoading(true);
        fetchInitialData();
      }
    } catch (error) {
      Alert.alert('Error', error.message || 'Failed to save quotation.');
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
        <Text style={styles.headerTitle}>Quotations Ledger</Text>
        <TouchableOpacity style={styles.addBtn} onPress={() => setModalVisible(true)}>
          <Text style={styles.addBtnText}>+ New Quotation</Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#FF5E3A" />
        </View>
      ) : (
        <FlatList
          data={quotations}
          keyExtractor={item => item.id}
          contentContainerStyle={styles.listContainer}
          renderItem={({ item }) => (
            <View style={styles.quotationCard}>
              <View style={styles.qHeader}>
                <Text style={styles.qNumber}>{item.quotation_number}</Text>
                <Text style={styles.qDate}>{item.quotation_date}</Text>
              </View>
              <Text style={styles.qCustomer}>{getCustomerName(item.customer_id)}</Text>
              <Text style={styles.qRoute}>🛣️ {item.from_city} ➜ {item.to_city}</Text>
              <View style={styles.qFooter}>
                <Text style={styles.qGst}>GST Tax: {item.gst_rate}%</Text>
                <Text style={styles.qTotal}>Total: ₹{parseFloat(item.grand_total).toLocaleString('en-IN')}</Text>
              </View>
            </View>
          )}
          ListEmptyComponent={
            <Text style={styles.emptyText}>No estimations generated yet.</Text>
          }
        />
      )}

      {/* Add Quotation Drawer Modal */}
      <Modal visible={modalVisible} animationType="slide" transparent>
        <View style={styles.modalBg}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>New Shifting Quotation</Text>
            <ScrollView style={styles.formScroll}>
              
              {/* Customer Selector */}
              <View style={styles.inputGroup}>
                <Text style={styles.label}>Select Customer *</Text>
                <View style={styles.pickerBorder}>
                  <Picker
                    selectedValue={customerId}
                    onValueChange={(val) => {
                      setCustomerId(val);
                      const c = customers.find(item => item.id === val);
                      if (c) setPhone(c.phone);
                    }}
                    style={styles.picker}
                  >
                    <Picker.Item label="-- Select Customer --" value="" />
                    {customers.map(c => (
                      <Picker.Item key={c.id} label={`${c.name} (${c.phone})`} value={c.id} />
                    ))}
                  </Picker>
                </View>
              </View>

              {/* Transport Routing */}
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

              {/* Items Inventory Checklist */}
              <Text style={styles.sectionHeading}>Inventory Items Checklist</Text>
              <View style={styles.checklistCard}>
                {Object.keys(checklist).map(key => (
                  <View key={key} style={styles.checkRow}>
                    <Text style={styles.checkItemName}>{key}</Text>
                    <View style={styles.stepperContainer}>
                      <TouchableOpacity style={styles.stepBtn} onPress={() => handleQtyChange(key, 'minus')}>
                        <Text style={styles.stepBtnText}>-</Text>
                      </TouchableOpacity>
                      <Text style={styles.stepVal}>{checklist[key]}</Text>
                      <TouchableOpacity style={[styles.stepBtn, styles.stepPlusBtn]} onPress={() => handleQtyChange(key, 'plus')}>
                        <Text style={[styles.stepBtnText, styles.stepPlusText]}>+</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                ))}
              </View>

              {/* Shifting Estimations Rates */}
              <Text style={styles.sectionHeading}>Transportation Cost Breakdown (₹)</Text>
              <View style={styles.row}>
                <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                  <Text style={styles.label}>Packing Charges</Text>
                  <TextInput style={styles.input} keyboardType="numeric" value={packing} onChangeText={setPacking} />
                </View>
                <View style={[styles.inputGroup, { flex: 1 }]}>
                  <Text style={styles.label}>Unpacking Charges</Text>
                  <TextInput style={styles.input} keyboardType="numeric" value={unpacking} onChangeText={setUnpacking} />
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
                  <Text style={styles.label}>Storage Charges</Text>
                  <TextInput style={styles.input} keyboardType="numeric" value={storage} onChangeText={setStorage} />
                </View>
                <View style={[styles.inputGroup, { flex: 1 }]}>
                  <Text style={styles.label}>Transit Insurance</Text>
                  <TextInput style={styles.input} keyboardType="numeric" value={insurance} onChangeText={setInsurance} />
                </View>
              </View>

              <View style={styles.row}>
                <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                  <Text style={styles.label}>Escort Charges</Text>
                  <TextInput style={styles.input} keyboardType="numeric" value={escort} onChangeText={setEscort} />
                </View>
                <View style={[styles.inputGroup, { flex: 1 }]}>
                  <Text style={styles.label}>GST Tax Rate (%)</Text>
                  <TextInput style={styles.input} keyboardType="numeric" value={gstRate} onChangeText={setGstRate} />
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
                  <Text style={styles.totalLabel}>Estimated Grand Total:</Text>
                  <Text style={styles.totalValue}>₹{getGrandTotal().toLocaleString('en-IN')}</Text>
                </View>
              </View>

            </ScrollView>

            <View style={styles.modalActions}>
              <TouchableOpacity style={styles.cancelBtn} onPress={() => setModalVisible(false)} disabled={submitting}>
                <Text style={styles.cancelBtnText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={submitting}>
                {submitting ? <ActivityIndicator color="#ffffff" /> : <Text style={styles.saveBtnText}>Generate Quote</Text>}
              </TouchableOpacity>
            </View>
          </View>
        </View>
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
  quotationCard: {
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
  qHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  qNumber: {
    fontSize: 13,
    fontWeight: '800',
    color: '#FF5E3A',
  },
  qDate: {
    fontSize: 11,
    color: '#94A3B8',
  },
  qCustomer: {
    fontSize: 14,
    fontWeight: '700',
    color: '#1E293B',
    marginBottom: 4,
  },
  qRoute: {
    fontSize: 12,
    color: '#475569',
    marginBottom: 10,
  },
  qFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
    paddingTop: 8,
  },
  qGst: {
    fontSize: 11,
    color: '#64748B',
    fontWeight: '600',
  },
  qTotal: {
    fontSize: 13,
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
  checklistCard: {
    backgroundColor: '#F8FAFC',
    borderRadius: 8,
    padding: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  checkRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 6,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  checkItemName: {
    fontSize: 12,
    fontWeight: '600',
    color: '#334155',
  },
  stepperContainer: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  stepBtn: {
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: '#E2E8F0',
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepPlusBtn: {
    backgroundColor: '#FFF1EE',
    borderWidth: 1,
    borderColor: '#FF5E3A',
  },
  stepBtnText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#475569',
  },
  stepPlusText: {
    color: '#FF5E3A',
  },
  stepVal: {
    width: 30,
    textAlign: 'center',
    fontSize: 13,
    fontWeight: '700',
    color: '#0F172A',
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
});
