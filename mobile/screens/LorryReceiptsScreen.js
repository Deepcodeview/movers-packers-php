import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, FlatList, TextInput, TouchableOpacity, Modal, ActivityIndicator, Alert, SafeAreaView, ScrollView, KeyboardAvoidingView, Platform, Linking } from 'react-native';
import { getLorryReceipts, getInvoices, getQuotations, getCustomers, addLorryReceipt } from '../utils/api';
import { Picker } from '@react-native-picker/picker';

export default function LorryReceiptsScreen() {
  const [lrs, setLrs] = useState([]);
  const [invoices, setInvoices] = useState([]);
  const [quotations, setQuotations] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [modalVisible, setModalVisible] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // Form Fields
  const [invoiceId, setInvoiceId] = useState('');
  const [lrDate, setLrDate] = useState(new Date().toISOString().split('T')[0]);

  const [consignorName, setConsignorName] = useState('');
  const [consignorMobile, setConsignorMobile] = useState('');
  const [consignorGstin, setConsignorGstin] = useState('');
  const [consigneeName, setConsigneeName] = useState('');
  const [consigneeMobile, setConsigneeMobile] = useState('');
  const [consigneeGstin, setConsigneeGstin] = useState('');

  const [fromAddress, setFromAddress] = useState('');
  const [toAddress, setToAddress] = useState('');

  const [vehicleNumber, setVehicleNumber] = useState('');
  const [driverName, setDriverName] = useState('');
  const [driverMobile, setDriverMobile] = useState('');

  const [articlesCount, setArticlesCount] = useState('0');
  const [description, setDescription] = useState('Household Goods Shifting');
  const [goodsValue, setGoodsValue] = useState('0');

  const [freightCharges, setFreightCharges] = useState('0');
  const [toPayBilling, setToPayBilling] = useState('To Pay');

  const fetchInitialData = async () => {
    try {
      const lrRes = await getLorryReceipts();
      const invRes = await getInvoices();
      const qRes = await getQuotations();
      const cRes = await getCustomers();
      if (lrRes.success) setLrs(lrRes.lorry_receipts || []);
      if (invRes.success) setInvoices(invRes.invoices || []);
      if (qRes.success) setQuotations(qRes.quotations || []);
      if (cRes.success) setCustomers(cRes.customers || []);
    } catch (error) {
      console.error('Error fetching lorry receipts details:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchInitialData();
  }, []);

  const handleInvoiceChange = (invId) => {
    setInvoiceId(invId);
    if (!invId) return;

    const inv = invoices.find(item => item.id === invId);
    if (inv) {
      // 1. Fetch Customer details
      const cust = customers.find(item => item.id === inv.customer_id);
      if (cust) {
        setConsigneeName(cust.name);
        setConsigneeMobile(cust.phone);
        setConsigneeGstin(cust.gstin || '');
        setToAddress(cust.address || inv.to_city);
      }

      // 2. Fetch origin company settings or standard consignor defaults
      setConsignorName('OM GUPTESWAR PACKERS & MOVERS');
      setConsignorMobile('+91 99999 88888');
      setFromAddress(inv.from_city);

      setVehicleNumber(inv.vehicle_number || '');
      setDriverName(inv.driver_name || '');
      setGoodsValue(inv.grand_total.toString());
      setFreightCharges(inv.freight_charge.toString());

      // 3. Process inventory checklist parsing from quotation link
      if (inv.quotation_id) {
        const q = quotations.find(item => item.id === inv.quotation_id);
        if (q && q.items) {
          try {
            const items = JSON.parse(q.items);
            if (items && typeof items === 'object') {
              let totalQty = 0;
              let itemsList = [];
              Object.keys(items).forEach(name => {
                const qty = parseInt(items[name]);
                if (qty > 0) {
                  totalQty += qty;
                  itemsList.push(`${qty} ${name}`);
                }
              });
              setArticlesCount(totalQty.toString());
              if (itemsList.length > 0) {
                setDescription(itemsList.join(', '));
              }
            }
          } catch (e) {
            console.error('JSON items parse error in Mobile Bilty generator:', e);
          }
        }
      }
    }
  };

  const getCustomerName = (cId) => {
    const c = customers.find(item => item.id === cId);
    return c ? c.name : 'Unknown Customer';
  };

  const handleSave = async () => {
    if (!invoiceId) {
      Alert.alert('Validation Error', 'Please choose an invoice to link.');
      return;
    }
    if (!consigneeName.trim() || !consigneeMobile.trim()) {
      Alert.alert('Validation Error', 'Consignee Name and Mobile are required.');
      return;
    }

    setSubmitting(true);
    try {
      const response = await addLorryReceipt({
        invoice_id: invoiceId,
        lr_date: lrDate,
        consignor_name: consignorName.trim(),
        consignor_mobile: consignorMobile.trim(),
        consignor_gstin: consignorGstin.trim(),
        consignee_name: consigneeName.trim(),
        consignee_mobile: consigneeMobile.trim(),
        consignee_gstin: consigneeGstin.trim(),
        from_address: fromAddress.trim(),
        to_address: toAddress.trim(),
        vehicle_number: vehicleNumber.trim(),
        driver_name: driverName.trim(),
        driver_mobile: driverMobile.trim(),
        articles_count: parseInt(articlesCount) || 0,
        description: description.trim(),
        goods_value: parseFloat(goodsValue) || 0,
        freight_charges: parseFloat(freightCharges) || 0,
        to_pay_billing: toPayBilling,
      });

      if (response.success) {
        Alert.alert('Success', `Bilty / LR ${response.lr_no} generated successfully!`);
        setModalVisible(false);
        // Clear inputs
        setInvoiceId('');
        setConsigneeName('');
        setConsigneeMobile('');
        setConsigneeGstin('');
        setVehicleNumber('');
        setDriverName('');
        setArticlesCount('0');
        setDescription('Household Goods Shifting');
        setGoodsValue('0');
        // Reload list
        setLoading(true);
        fetchInitialData();
      }
    } catch (error) {
      Alert.alert('Error', error.message || 'Failed to save Lorry Receipt.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Header Bar */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Lorry Receipts (Bilty)</Text>
        <TouchableOpacity style={styles.addBtn} onPress={() => setModalVisible(true)}>
          <Text style={styles.addBtnText}>+ Generate Bilty</Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#FF5E3A" />
        </View>
      ) : (
        <FlatList
          data={lrs}
          keyExtractor={item => item.id}
          contentContainerStyle={styles.listContainer}
          renderItem={({ item }) => (
            <View style={styles.lrCard}>
              <View style={styles.lrHeader}>
                <Text style={styles.lrNoText}>{item.lr_no}</Text>
                <Text style={styles.lrDateText}>{item.lr_date}</Text>
              </View>
              <Text style={styles.lrClient}>To: {item.consignee_name} ({item.consignee_mobile})</Text>
              <Text style={styles.lrRoute}>🛣️ {item.from_address} ➜ {item.to_address}</Text>
              <View style={styles.lrFooter}>
                <Text style={styles.lrVehicle}>Truck: {item.vehicle_number || 'N/A'}</Text>
                <Text style={styles.lrFreight}>Freight: ₹{parseFloat(item.freight_charges).toLocaleString('en-IN')}</Text>
              </View>
              <TouchableOpacity 
                style={styles.printShareBtn} 
                onPress={() => Linking.openURL(`https://manage.deepsde.in/lorry_receipts.php?action=view&id=${item.id}`)}
              >
                <Text style={styles.printShareBtnText}>🖨️ View / Print Lorry Receipt (Bilty)</Text>
              </TouchableOpacity>
            </View>
          )}
          ListEmptyComponent={
            <Text style={styles.emptyText}>No lorry receipts generated yet.</Text>
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
              <Text style={styles.modalTitle}>New Transport Bilty</Text>
              <ScrollView style={styles.formScroll} contentContainerStyle={{ paddingBottom: 20 }}>
                
                {/* Select invoice link */}
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>Select Linked Invoice *</Text>
                  <View style={styles.pickerBorder}>
                    <Picker
                      selectedValue={invoiceId}
                      onValueChange={handleInvoiceChange}
                      style={styles.picker}
                    >
                      <Picker.Item label="-- Choose Invoice --" value="" />
                      {invoices.map(i => (
                        <Picker.Item key={i.id} label={`${i.invoice_number} - ${getCustomerName(i.customer_id)}`} value={i.id} />
                      ))}
                    </Picker>
                  </View>
                </View>

                {/* Consignor details */}
                <Text style={styles.sectionHeading}>Consignor (Sender) Details</Text>
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>Sender / Company Name</Text>
                  <TextInput style={styles.input} value={consignorName} onChangeText={setConsignorName} />
                </View>
                <View style={styles.row}>
                  <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                    <Text style={styles.label}>Sender Mobile</Text>
                    <TextInput style={styles.input} value={consignorMobile} onChangeText={setConsignorMobile} />
                  </View>
                  <View style={[styles.inputGroup, { flex: 1 }]}>
                    <Text style={styles.label}>Sender GSTIN</Text>
                    <TextInput style={styles.input} autoCapitalize="characters" value={consignorGstin} onChangeText={setConsignorGstin} />
                  </View>
                </View>
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>Origin Starting Address</Text>
                  <TextInput style={styles.input} value={fromAddress} onChangeText={setFromAddress} />
                </View>

                {/* Consignee details */}
                <Text style={styles.sectionHeading}>Consignee (Receiver) Details</Text>
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>Receiver Name *</Text>
                  <TextInput style={styles.input} value={consigneeName} onChangeText={setConsigneeName} />
                </View>
                <View style={styles.row}>
                  <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                    <Text style={styles.label}>Receiver Mobile *</Text>
                    <TextInput style={styles.input} value={consigneeMobile} onChangeText={setConsigneeMobile} />
                  </View>
                  <View style={[styles.inputGroup, { flex: 1 }]}>
                    <Text style={styles.label}>Receiver GSTIN</Text>
                    <TextInput style={styles.input} autoCapitalize="characters" value={consigneeGstin} onChangeText={setConsigneeGstin} />
                  </View>
                </View>
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>Delivery Destination Address</Text>
                  <TextInput style={styles.input} value={toAddress} onChangeText={setToAddress} />
                </View>

                {/* Transit Carriage specs */}
                <Text style={styles.sectionHeading}>Carriage & Dispatch Specifications</Text>
                <View style={styles.row}>
                  <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                    <Text style={styles.label}>Vehicle Number</Text>
                    <TextInput style={styles.input} autoCapitalize="characters" value={vehicleNumber} onChangeText={setVehicleNumber} />
                  </View>
                  <View style={[styles.inputGroup, { flex: 1 }]}>
                    <Text style={styles.label}>Driver Name</Text>
                    <TextInput style={styles.input} value={driverName} onChangeText={setDriverName} />
                  </View>
                </View>
                
                <View style={styles.row}>
                  <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                    <Text style={styles.label}>Articles Count (Boxes/Packages)</Text>
                    <TextInput style={styles.input} keyboardType="numeric" value={articlesCount} onChangeText={setArticlesCount} />
                  </View>
                  <View style={[styles.inputGroup, { flex: 1 }]}>
                    <Text style={styles.label}>Goods Value Amount (₹)</Text>
                    <TextInput style={styles.input} keyboardType="numeric" value={goodsValue} onChangeText={setGoodsValue} />
                  </View>
                </View>

                <View style={styles.inputGroup}>
                  <Text style={styles.label}>Description of Shifting Goods</Text>
                  <TextInput style={[styles.input, { height: 60 }]} multiline value={description} onChangeText={setDescription} />
                </View>

                <View style={styles.row}>
                  <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                    <Text style={styles.label}>Freight Freight Charges (₹)</Text>
                    <TextInput style={styles.input} keyboardType="numeric" value={freightCharges} onChangeText={setFreightCharges} />
                  </View>
                  <View style={[styles.inputGroup, { flex: 1 }]}>
                    <Text style={styles.label}>Billing Terms Type</Text>
                    <View style={styles.pickerBorder}>
                      <Picker
                        selectedValue={toPayBilling}
                        onValueChange={setToPayBilling}
                        style={styles.picker}
                      >
                        <Picker.Item label="To Pay" value="To Pay" />
                        <Picker.Item label="Paid" value="Paid" />
                        <Picker.Item label="T.B.B. (To Be Billed)" value="T.B.B." />
                      </Picker>
                    </View>
                  </View>
                </View>

                <View style={[styles.modalActions, { marginTop: 20 }]}>
                  <TouchableOpacity style={styles.cancelBtn} onPress={() => setModalVisible(false)} disabled={submitting}>
                    <Text style={styles.cancelBtnText}>Cancel</Text>
                  </TouchableOpacity>
                  <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={submitting}>
                    {submitting ? <ActivityIndicator color="#ffffff" /> : <Text style={styles.saveBtnText}>Save Bilty</Text>}
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
  lrCard: {
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
  lrHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  lrNoText: {
    fontSize: 13,
    fontWeight: '800',
    color: '#FF5E3A',
  },
  lrDateText: {
    fontSize: 11,
    color: '#94A3B8',
  },
  lrClient: {
    fontSize: 14,
    fontWeight: '700',
    color: '#1E293B',
    marginBottom: 4,
  },
  lrRoute: {
    fontSize: 12,
    color: '#475569',
    marginBottom: 10,
  },
  lrFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
    paddingTop: 8,
  },
  lrVehicle: {
    fontSize: 11,
    color: '#64748B',
    fontWeight: '600',
  },
  lrFreight: {
    fontSize: 12,
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
    fontSize: 12,
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
