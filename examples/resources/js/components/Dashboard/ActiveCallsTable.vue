<template>
  <div class="active-calls-table">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          <i class="bi bi-telephone-fill"></i>
          Active Calls
          <span class="badge bg-primary ms-2">{{ calls.length }}</span>
        </h5>
        <button 
          class="btn btn-sm btn-outline-secondary" 
          @click="refreshCalls"
          :disabled="loading"
        >
          <i class="bi bi-arrow-clockwise"></i>
          Refresh
        </button>
      </div>

      <div class="card-body p-0">
        <!-- Loading State -->
        <div v-if="loading && calls.length === 0" class="text-center p-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="calls.length === 0" class="text-center p-5 text-muted">
          <i class="bi bi-telephone-x" style="font-size: 3rem;"></i>
          <p class="mt-3">No active calls at the moment</p>
        </div>

        <!-- Calls Table -->
        <div v-else class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Extension</th>
                <th>From</th>
                <th>To</th>
                <th>Status</th>
                <th>Duration</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="call in calls" 
                :key="call.id"
                :class="getRowClass(call)"
              >
                <td>
                  <strong>{{ call.extension || 'N/A' }}</strong>
                  <div v-if="call.user" class="small text-muted">
                    {{ call.user.name }}
                  </div>
                </td>
                <td>
                  <div>{{ formatPhoneNumber(call.caller_id) }}</div>
                  <div class="small text-muted">{{ call.caller_name || 'Unknown' }}</div>
                </td>
                <td>
                  <strong>{{ formatPhoneNumber(call.destination) }}</strong>
                </td>
                <td>
                  <span :class="getStatusBadgeClass(call.status)">
                    {{ formatStatus(call.status) }}
                  </span>
                </td>
                <td>
                  <div class="font-monospace">{{ formatDuration(call) }}</div>
                </td>
                <td>
                  <div class="btn-group btn-group-sm" role="group">
                    <button 
                      v-if="canHangup"
                      class="btn btn-outline-danger"
                      @click="hangupCall(call)"
                      :disabled="actionLoading[call.id]"
                      title="Hangup Call"
                    >
                      <i class="bi bi-telephone-x"></i>
                    </button>
                    <button 
                      class="btn btn-outline-info"
                      @click="viewDetails(call)"
                      title="View Details"
                    >
                      <i class="bi bi-info-circle"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Call Details Modal -->
    <CallDetailsModal 
      v-if="selectedCall"
      :call="selectedCall"
      @close="selectedCall = null"
    />
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import CallDetailsModal from './CallDetailsModal.vue';

export default {
  name: 'ActiveCallsTable',
  
  components: {
    CallDetailsModal
  },

  props: {
    autoRefresh: {
      type: Boolean,
      default: true
    },
    refreshInterval: {
      type: Number,
      default: 2000 // 2 seconds
    },
    canHangup: {
      type: Boolean,
      default: false
    }
  },

  setup(props, { emit }) {
    const calls = ref([]);
    const loading = ref(false);
    const actionLoading = ref({});
    const selectedCall = ref(null);
    let refreshTimer = null;

    // Fetch active calls from API
    const fetchCalls = async () => {
      try {
        loading.value = true;
        const response = await axios.get('/api/calls/active');
        
        if (response.data.success) {
          calls.value = response.data.data;
          emit('calls-updated', calls.value);
        }
      } catch (error) {
        console.error('Error fetching active calls:', error);
        emit('error', error);
      } finally {
        loading.value = false;
      }
    };

    // Manual refresh
    const refreshCalls = () => {
      fetchCalls();
    };

    // Hangup a call
    const hangupCall = async (call) => {
      if (!confirm(`Are you sure you want to hangup this call?`)) {
        return;
      }

      try {
        actionLoading.value[call.id] = true;
        
        const response = await axios.post(`/api/calls/${call.channel}/hangup`);
        
        if (response.data.success) {
          // Show success message
          emit('call-hung-up', call);
          
          // Remove from list immediately (will be confirmed by refresh)
          calls.value = calls.value.filter(c => c.id !== call.id);
        } else {
          alert('Failed to hangup call: ' + response.data.message);
        }
      } catch (error) {
        console.error('Error hanging up call:', error);
        alert('An error occurred while hanging up the call');
      } finally {
        actionLoading.value[call.id] = false;
      }
    };

    // View call details
    const viewDetails = (call) => {
      selectedCall.value = call;
    };

    // Format phone number
    const formatPhoneNumber = (number) => {
      if (!number) return 'N/A';
      
      // Simple US format: (XXX) XXX-XXXX
      const cleaned = String(number).replace(/\D/g, '');
      if (cleaned.length === 10) {
        return `(${cleaned.slice(0, 3)}) ${cleaned.slice(3, 6)}-${cleaned.slice(6)}`;
      }
      return number;
    };

    // Format status text
    const formatStatus = (status) => {
      const statusMap = {
        'ringing': 'Ringing',
        'up': 'Connected',
        'busy': 'Busy',
        'down': 'Ended'
      };
      return statusMap[status] || status;
    };

    // Get status badge class
    const getStatusBadgeClass = (status) => {
      const baseClass = 'badge';
      const statusClasses = {
        'ringing': 'bg-warning',
        'up': 'bg-success',
        'busy': 'bg-danger',
        'down': 'bg-secondary'
      };
      return `${baseClass} ${statusClasses[status] || 'bg-info'}`;
    };

    // Get table row class
    const getRowClass = (call) => {
      if (call.status === 'ringing') return 'table-warning';
      if (call.status === 'up') return 'table-success';
      return '';
    };

    // Format duration (real-time update)
    const formatDuration = (call) => {
      if (!call.started_at) return '00:00:00';
      
      const start = new Date(call.started_at);
      const now = new Date();
      const diffSeconds = Math.floor((now - start) / 1000);
      
      const hours = Math.floor(diffSeconds / 3600);
      const minutes = Math.floor((diffSeconds % 3600) / 60);
      const seconds = diffSeconds % 60;
      
      return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    };

    // Setup auto-refresh
    const setupAutoRefresh = () => {
      if (props.autoRefresh && props.refreshInterval > 0) {
        refreshTimer = setInterval(fetchCalls, props.refreshInterval);
      }
    };

    // Lifecycle hooks
    onMounted(() => {
      fetchCalls();
      setupAutoRefresh();

      // Listen to WebSocket events for real-time updates
      if (window.Echo) {
        window.Echo.channel('calls')
          .listen('NewCallEvent', (event) => {
            console.log('New call event:', event);
            fetchCalls(); // Refresh list
          })
          .listen('CallHangupEvent', (event) => {
            console.log('Call hangup event:', event);
            fetchCalls(); // Refresh list
          });
      }
    });

    onUnmounted(() => {
      if (refreshTimer) {
        clearInterval(refreshTimer);
      }

      // Leave WebSocket channel
      if (window.Echo) {
        window.Echo.leave('calls');
      }
    });

    return {
      calls,
      loading,
      actionLoading,
      selectedCall,
      refreshCalls,
      hangupCall,
      viewDetails,
      formatPhoneNumber,
      formatStatus,
      getStatusBadgeClass,
      getRowClass,
      formatDuration
    };
  }
};
</script>

<style scoped>
.active-calls-table {
  margin-bottom: 1.5rem;
}

.table {
  font-size: 0.9rem;
}

.table td {
  vertical-align: middle;
}

.font-monospace {
  font-family: 'Courier New', monospace;
  font-size: 0.95rem;
}

.table-warning {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    background-color: rgba(255, 193, 7, 0.1);
  }
  50% {
    background-color: rgba(255, 193, 7, 0.2);
  }
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
}
</style>

