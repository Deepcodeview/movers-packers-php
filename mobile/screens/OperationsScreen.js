import React, { useState, useEffect } from 'react';
import { StyleSheet, View, Text, TouchableOpacity, SafeAreaView } from 'react-native';
import LorryReceiptsScreen from './LorryReceiptsScreen';
import PaymentsScreen from './PaymentsScreen';
import AuditsScreen from './AuditsScreen';

export default function OperationsScreen({ route }) {
  const [activeSegment, setActiveSegment] = useState('bilty'); // 'bilty', 'payments', or 'audits'

  useEffect(() => {
    if (route?.params?.activeSegment) {
      setActiveSegment(route.params.activeSegment);
    }
  }, [route?.params?.activeSegment]);

  return (
    <SafeAreaView style={styles.container}>
      {/* Segmented Top Header Picker */}
      <View style={styles.segmentContainer}>
        <TouchableOpacity 
          style={[styles.segmentBtn, activeSegment === 'bilty' && styles.activeSegmentBtn]}
          onPress={() => setActiveSegment('bilty')}
        >
          <Text style={[styles.segmentText, activeSegment === 'bilty' && styles.activeSegmentText]}>
            🚛 Bilty
          </Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={[styles.segmentBtn, activeSegment === 'payments' && styles.activeSegmentBtn]}
          onPress={() => setActiveSegment('payments')}
        >
          <Text style={[styles.segmentText, activeSegment === 'payments' && styles.activeSegmentText]}>
            💸 Collections
          </Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={[styles.segmentBtn, activeSegment === 'audits' && styles.activeSegmentBtn]}
          onPress={() => setActiveSegment('audits')}
        >
          <Text style={[styles.segmentText, activeSegment === 'audits' && styles.activeSegmentText]}>
            📋 GST & Audits
          </Text>
        </TouchableOpacity>
      </View>

      {/* Render Selected Operations Screen */}
      <View style={styles.contentContainer}>
        {activeSegment === 'bilty' ? (
          <LorryReceiptsScreen />
        ) : activeSegment === 'payments' ? (
          <PaymentsScreen />
        ) : (
          <AuditsScreen />
        )}
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  segmentContainer: {
    flexDirection: 'row',
    backgroundColor: '#ffffff',
    padding: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  segmentBtn: {
    flex: 1,
    paddingVertical: 10,
    alignItems: 'center',
    borderRadius: 6,
  },
  activeSegmentBtn: {
    backgroundColor: '#FFF1EE',
    borderWidth: 1,
    borderColor: '#FF5E3A',
  },
  segmentText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#64748B',
  },
  activeSegmentText: {
    color: '#FF5E3A',
  },
  contentContainer: {
    flex: 1,
  },
});
