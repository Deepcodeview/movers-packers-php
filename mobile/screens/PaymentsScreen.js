import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, FlatList, TextInput, TouchableOpacity, Modal, ActivityIndicator, Alert, SafeAreaView, ScrollView, KeyboardAvoidingView, Platform, Linking } from 'react-native';
import { getPayments, getInvoices, addPayment, getCustomers } from '../utils/api';
import { Picker } from '@react-native-picker/picker';

export default function PaymentsScreen() {
  const [payments, setPayments] = useState([]);
  const [invoices, setInvoices] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [modalVisible, setModalVisible] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // Form Fields
  const [invoiceId, setInvoiceId] = useState('');
  const [amount, setAmount] = useState('');
  const [paymentMode, setPaymentMode] = useState('Cash');
  const [referenceNumber, setReferenceNumber] = useState('');
  const [remarks, setRemarks] = useState('');
  const [paymentDate, setPaymentDate] = useState(new Date().toISOString().split('T')[0]);

  const [maxAmount, setMaxAmount] = useState(null);

  const fetchInitialData = async () => {
    try {
      const pRes = await getPayments();
      const invRes = await getInvoices();
      const cRes = await getCustomers();
      if (pRes.success) setPayments(pRes.payments || []);
      if (invRes.success) setInvoices(invRes.invoices || []);
      if (cRes.success) setCustomers(cRes.customers || []);
    } catch (error) {
      console.error('Error fetching payments details:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchInitialData();
  }, []);

  const handleInvoiceChange = (invId) => {
    setInvoiceId(invId);
    if (!invId) {
      setMaxAmount(null);
      setAmount('');
      return;
    }

    const inv = invoices.find(item => item.id === invId);
    if (inv) {
      setMaxAmount(parseFloat(inv.outstanding_balance));
      setAmount(inv.outstanding_balance.toString());
    }
  };

  const handleSave = async () => {
    if (!invoiceId) {
      Alert.alert('Validation Error', 'Please choose an invoice.');
      return;
    }

    const payAmt = parseFloat(amount);
    if (isNaN(payAmt) || payAmt <= 0) {
      Alert.alert('Validation Error', 'Please enter a valid positive payment amount.');
      return;
    }

    if (maxAmount !== null && payAmt > maxAmount) {
      Alert.alert('Validation Error', `Amount exceeds outstanding balance. Max allowed: ₹${maxAmount.toLocaleString('en-IN')}`);
      return;
    }

    setSubmitting(true);
    try {
      const response = await addPayment({
        invoice_id: invoiceId,
        amount: payAmt,
        payment_mode: paymentMode,
        reference_number: referenceNumber.trim(),
        remarks: remarks.trim(),
        payment_date: paymentDate,
      });

      if (response.success) {
        Alert.alert('Success', 'Payment collection logged successfully!');
        setModalVisible(false);
        // Clear inputs
        setInvoiceId('');
        setAmount('');
        setReferenceNumber('');
        setRemarks('');
        // Reload list
        setLoading(true);
        fetchInitialData();
      }
    } catch (error) {
      Alert.alert('Error', error.message || 'Failed to save payment.');
    } finally {
      setSubmitting(false);
    }
  };

  const getCustomerNameFromInvoice = (invId) => {
    const inv = invoices.find(item => item.id === invId);
    if (!inv) return 'Unknown Customer';
    const c = customers.find(item => item.id === inv.customer_id);
    return c ? c.name : 'Unknown Customer';
  };

  const getInvoiceNumber = (invId) => {
    const inv = invoices.find(item => item.id === invId);
    return inv ? inv.invoice_number : 'N/A';
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Header Bar */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Collection Ledgers</Text>
        <TouchableOpacity style={styles.addBtn} onPress={() => setModalVisible(true)}>
          <Text style={styles.addBtnText}>+ Record Payment</Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#FF5E3A" />
        </View>
      ) : (
        <FlatList
          data={payments}
          keyExtractor={item => item.id}
          contentContainerStyle={styles.listContainer}
          renderItem={({ item }) => (
            <View style={styles.paymentCard}>
              <View style={styles.payHeader}>
                <Text style={styles.payNumber}>REC-{item.id.substring(item.id.length - 6).toUpperCase()}</Text>
                <Text style={styles.payDate}>{item.payment_date}</Text>
              </View>
              <Text style={styles.payCustomer}>{getCustomerNameFromInvoice(item.invoice_id)}</Text>
              <Text style={styles.payMeta}>Towards Invoice: #{getInvoiceNumber(item.invoice_id)}</Text>
              
              <View style={styles.payFooter}>
                <View style={styles.modeBadge}>
                  <Text style={styles.modeText}>{item.payment_mode}</Text>
                </View>
                <Text style={styles.payAmtText}>₹{parseFloat(item.amount).toLocaleString('en-IN')}</Text>
              </View>
              <TouchableOpacity 
                style={styles.printShareBtn} 
                onPress={() => Linking.openURL(`https://manage.deepsde.in/payments.php?action=receipt&id=${item.id}`)}
              >
                <Text style={styles.printShareBtnText}>🖨️ View / Print Money Receipt Slip</Text>
              </TouchableOpacity>
            </View>
          )}
          ListEmptyComponent={
            <Text style={styles.emptyText}>No cash collections tracked yet.</Text>
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
              <Text style={styles.modalTitle}>Record Collection Payment</Text>
              <ScrollView style={styles.formScroll} contentContainerStyle={{ paddingBottom: 20 }}>
                
                {/* Select invoice link */}
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>Select Invoice *</Text>
                  <View style={styles.pickerBorder}>
                    <Picker
                      selectedValue={invoiceId}
                      onValueChange={handleInvoiceChange}
                      style={styles.picker}
                    >
                      <Picker.Item label="-- Choose Invoice --" value="" />
                      {invoices.filter(i => parseFloat(i.outstanding_balance) > 0).map(i => (
                        <Picker.Item key={i.id} label={`#${i.invoice_number} - ${getCustomerNameFromInvoice(i.id)} (Bal: ₹${parseFloat(i.outstanding_balance)})`} value={i.id} />
                      ))}
                    </Picker>
                  </View>
                </View>

                {/* Amount input */}
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>Collection Amount (₹) *</Text>
                  <TextInput style={styles.input} keyboardType="numeric" value={amount} onChangeText={setAmount} />
                  {maxAmount !== null && (
                    <Text style={styles.helperLabel}>Remaining outstanding: ₹{maxAmount.toLocaleString('en-IN')}</Text>
                  )}
                </View>

                {/* Payment Mode */}
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>Payment Method</Text>
                  <View style={styles.pickerBorder}>
                    <Picker
                      selectedValue={paymentMode}
                      onValueChange={setPaymentMode}
                      style={styles.picker}
                    >
                      <Picker.Item label="Cash" value="Cash" />
                      <Picker.Item label="Bank Transfer" value="Bank Transfer" />
                      <Picker.Item label="UPI / QR Scan" value="UPI" />
                      <Picker.Item label="Cheque" value="Cheque" />
                    </Picker>
                  </View>
                </View>

                {/* Ref Number */}
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>Transaction Reference / UPI ID</Text>
                  <TextInput style={styles.input} placeholder="Optional transaction ID" value={referenceNumber} onChangeText={setReferenceNumber} />
                </View>

                {/* Remarks */}
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>Internal Remarks</Text>
                  <TextInput style={styles.input} placeholder="e.g. Received by hand / driver" value={remarks} onChangeText={setRemarks} />
                </View>

                <View style={[styles.modalActions, { marginTop: 20 }]}>
                  <TouchableOpacity style={styles.cancelBtn} onPress={() => setModalVisible(false)} disabled={submitting}>
                    <Text style={styles.cancelBtnText}>Cancel</Text>
                  </TouchableOpacity>
                  <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={submitting}>
                    {submitting ? <ActivityIndicator color="#ffffff" /> : <Text style={styles.saveBtnText}>Save Payment</Text>}
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
  paymentCard: {
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
  payHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  payNumber: {
    fontSize: 13,
    fontWeight: '800',
    color: '#FF5E3A',
  },
  payDate: {
    fontSize: 11,
    color: '#94A3B8',
  },
  payCustomer: {
    fontSize: 14,
    fontWeight: '700',
    color: '#1E293B',
    marginBottom: 4,
  },
  payMeta: {
    fontSize: 12,
    color: '#475569',
    marginBottom: 12,
  },
  payFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
    paddingTop: 10,
  },
  modeBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    backgroundColor: '#F8FAFC',
  },
  modeText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#475569',
  },
  payAmtText: {
    fontSize: 14,
    fontWeight: '800',
    color: '#10B981',
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
  inputGroup: {
    marginBottom: 14,
  },
  label: {
    fontSize: 11,
    fontWeight: '600',
    color: '#475569',
    marginBottom: 6,
  },
  helperLabel: {
    fontSize: 11,
    color: '#FF5E3A',
    marginTop: 4,
    fontWeight: '600',
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
