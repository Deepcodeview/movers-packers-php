import React, { useState, useEffect } from 'react';
import { StyleSheet, View, ActivityIndicator } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { NavigationContainer } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { getLoggedUser } from './utils/api';
import { Ionicons } from '@expo/vector-icons';

// Import Screens
import LoginScreen from './screens/LoginScreen';
import DashboardScreen from './screens/DashboardScreen';
import CustomersScreen from './screens/CustomersScreen';
import QuotationsScreen from './screens/QuotationsScreen';
import InvoicesScreen from './screens/InvoicesScreen';
import OperationsScreen from './screens/OperationsScreen';

const Tab = createBottomTabNavigator();

export default function App() {
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(null);

  // Check login on launch
  useEffect(() => {
    async function checkAuth() {
      try {
        const storedUser = await getLoggedUser();
        if (storedUser) {
          setUser(storedUser);
        }
      } catch (e) {
        console.error('Auth restore failed:', e);
      } finally {
        setLoading(false);
      }
    }
    checkAuth();
  }, []);

  const handleLoginSuccess = (profile) => {
    setUser(profile);
  };

  const handleLogout = () => {
    setUser(null);
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#FF5E3A" />
      </View>
    );
  }

  if (!user) {
    return (
      <>
        <LoginScreen onLoginSuccess={handleLoginSuccess} />
        <StatusBar style="dark" />
      </>
    );
  }

  return (
    <NavigationContainer>
      <Tab.Navigator
        screenOptions={({ route }) => ({
          headerShown: false,
          tabBarActiveTintColor: '#FF5E3A',
          tabBarInactiveTintColor: '#94A3B8',
          tabBarStyle: {
            backgroundColor: '#ffffff',
            borderTopWidth: 1,
            borderTopColor: '#E2E8F0',
            height: 60,
            paddingBottom: 8,
            paddingTop: 8,
          },
          tabBarLabelStyle: {
            fontSize: 10,
            fontWeight: '600',
          },
          tabBarIcon: ({ color, size }) => {
            let iconName;
            if (route.name === 'Dashboard') {
              iconName = 'analytics-sharp';
            } else if (route.name === 'Clients') {
              iconName = 'people-sharp';
            } else if (route.name === 'Quotations') {
              iconName = 'document-text-sharp';
            } else if (route.name === 'Invoices') {
              iconName = 'receipt-sharp';
            } else if (route.name === 'Operations') {
              iconName = 'bus-sharp';
            }
            return <Ionicons name={iconName} size={size} color={color} />;
          },
        })}
      >
        <Tab.Screen name="Dashboard">
          {props => <DashboardScreen {...props} user={user} onLogout={handleLogout} />}
        </Tab.Screen>
        <Tab.Screen name="Clients" component={CustomersScreen} />
        <Tab.Screen name="Quotations" component={QuotationsScreen} />
        <Tab.Screen name="Invoices" component={InvoicesScreen} />
        <Tab.Screen name="Operations" component={OperationsScreen} />
      </Tab.Navigator>
      <StatusBar style="dark" />
    </NavigationContainer>
  );
}

const styles = StyleSheet.create({
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
  },
});
